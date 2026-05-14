<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\ValuablesHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Valuable;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

class ValuablesActionService
{
    use DominionGuardsTrait;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var ValuablesHelper */
    protected $valuablesHelper;

    public function __construct()
    {
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->valuablesHelper = app(ValuablesHelper::class);
    }

    /**
     * Begin an investigation. Throws GameException on validation failure.
     */
    public function startInvestigation(Dominion $dominion, Valuable $valuable, int $hours): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        if ($valuable->source_dominion_id !== $dominion->id) {
            throw new GameException('That valuable does not belong to you.');
        }

        if (!in_array($valuable->status, [Valuable::STATUS_DISCOVERED, Valuable::STATUS_TRANSFERRED], true)) {
            throw new GameException('That valuable is not in a state where it can be investigated.');
        }

        if ($valuable->is_listed) {
            throw new GameException('Unlist the valuable before starting an investigation.');
        }

        if ($hours < ValuablesHelper::MAX_INVESTIGATION_HOURS
            || $hours > ValuablesHelper::MIN_INVESTIGATION_HOURS
            || ($hours % ValuablesHelper::INVESTIGATION_HOUR_STEP) !== 0
        ) {
            throw new GameException('Invalid investigation duration.');
        }

        // Staleness check
        $age = $valuable->hoursOld();
        if ($age >= ValuablesHelper::EXPIRATION_HOURS) {
            $valuable->status = Valuable::STATUS_FAILED;
            $valuable->save();
            throw new GameException('This discovery is too old to act on.');
        }

        $staleChance = ($age / ValuablesHelper::EXPIRATION_HOURS) ** 2;
        if (random_chance($staleChance)) {
            $valuable->status = Valuable::STATUS_FAILED;
            $valuable->save();
            throw new GameException('Your spies arrive only to find the trail has gone cold. The valuable is lost.');
        }

        // Spy-hours were frozen at discovery time.
        $requiredSpyHours = $valuable->required_spy_hours;

        $spiesNeeded = (int) ceil($requiredSpyHours / $hours);
        $minSpies = (int) ceil($requiredSpyHours / ValuablesHelper::MIN_INVESTIGATION_HOURS);
        $maxSpies = (int) ceil($requiredSpyHours / ValuablesHelper::MAX_INVESTIGATION_HOURS);

        if ($spiesNeeded < $minSpies || $spiesNeeded > $maxSpies) {
            throw new GameException('That duration is outside the allowed range for this valuable.');
        }

        $available = $this->valuablesHelper->getAvailableSpies($dominion);
        if ($spiesNeeded > $available) {
            throw new GameException(sprintf(
                'You need %s spies to commit but only %s are available.',
                number_format($spiesNeeded),
                number_format($available)
            ));
        }

        // Regen gate: would adding one more investigation push regen <= 0?
        $currentRegen = $this->militaryCalculator->getSpyStrengthRegen($dominion);
        $projectedRegen = $currentRegen - ValuablesHelper::SPY_STRENGTH_PER_INVESTIGATION;
        if ($projectedRegen <= 0) {
            throw new GameException('Starting another investigation would prevent your spy strength from regenerating.');
        }

        DB::transaction(function () use ($valuable, $hours, $spiesNeeded) {
            // Start now; round only the completion time to a tick boundary.
            $startTime = now();
            $endTime = now()->copy()->startOfHour()->addHours($hours);

            $valuable->spies_assigned = $spiesNeeded;
            $valuable->status = Valuable::STATUS_INVESTIGATING;
            $valuable->investigation_started_at = $startTime;
            $valuable->investigation_ends_at = $endTime;
            $valuable->save();
        });

        return [
            'success' => true,
            'message' => sprintf(
                'You commit %s spies to investigate %s. The heist completes in %d hours.',
                number_format($spiesNeeded),
                $valuable->name,
                $hours
            ),
            'alert-type' => 'success',
        ];
    }

    /**
     * Abort an in-progress investigation. Returns the valuable to discovered.
     */
    public function cancelInvestigation(Dominion $dominion, Valuable $valuable): array
    {
        $this->guardLockedDominion($dominion);

        if ($valuable->source_dominion_id !== $dominion->id) {
            throw new GameException('That valuable does not belong to you.');
        }

        if ($valuable->status !== Valuable::STATUS_INVESTIGATING) {
            throw new GameException('That valuable is not under investigation.');
        }

        DB::transaction(function () use ($valuable) {
            $valuable->status = Valuable::STATUS_DISCOVERED;
            $valuable->spies_assigned = null;
            $valuable->investigation_started_at = null;
            $valuable->investigation_ends_at = null;
            $valuable->save();
        });

        return [
            'success' => true,
            'message' => sprintf('Investigation of %s has been called off.', $valuable->name),
            'alert-type' => 'success',
        ];
    }

    /**
     * Sell a stolen valuable to the black market for its decayed price.
     */
    public function sellValuable(Dominion $dominion, Valuable $valuable): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        if ($valuable->source_dominion_id !== $dominion->id) {
            throw new GameException('That valuable does not belong to you.');
        }

        if ($valuable->status !== Valuable::STATUS_STOLEN) {
            throw new GameException('You can only sell valuables you have already stolen.');
        }

        $price = $this->valuablesHelper->getCurrentSalePrice($valuable);

        DB::transaction(function () use ($dominion, $valuable, $price) {
            $dominion->resource_platinum += $price;
            $valuable->status = Valuable::STATUS_SOLD;
            $valuable->sold_price = $price;
            $valuable->save();
            $dominion->save(['event' => HistoryService::EVENT_ACTION_SELL_VALUABLE]);
        });

        return [
            'success' => true,
            'message' => sprintf(
                'You sell %s for %s platinum.',
                $valuable->name,
                number_format($price)
            ),
            'alert-type' => 'success',
        ];
    }

    /**
     * List a discovered valuable for transfer to a realm mate.
     */
    public function listValuable(Dominion $dominion, Valuable $valuable): array
    {
        $this->guardLockedDominion($dominion);

        if ($valuable->source_dominion_id !== $dominion->id) {
            throw new GameException('That valuable does not belong to you.');
        }

        if (!in_array($valuable->status, [Valuable::STATUS_DISCOVERED, Valuable::STATUS_TRANSFERRED], true)) {
            throw new GameException('Only undiscovered or freshly-transferred valuables can be listed.');
        }

        if ($valuable->is_listed) {
            throw new GameException('That valuable is already listed.');
        }

        $valuable->is_listed = true;
        $valuable->save();

        return [
            'success' => true,
            'message' => sprintf('%s is now listed for transfer.', $valuable->name),
            'alert-type' => 'success',
        ];
    }

    /**
     * Remove a transfer listing. Anyone in the seller's realm can call this
     * (the bounty board surfaces the action for the seller themselves).
     */
    public function unlistValuable(Dominion $dominion, Valuable $valuable): array
    {
        $this->guardLockedDominion($dominion);

        if (!$valuable->is_listed) {
            throw new GameException('That valuable is not listed.');
        }

        $sellerRealmId = $valuable->sourceDominion->realm_id ?? null;
        if ($valuable->source_dominion_id !== $dominion->id && $dominion->realm_id !== $sellerRealmId) {
            throw new GameException('You cannot unlist that valuable.');
        }

        $valuable->is_listed = false;
        $valuable->save();

        return [
            'success' => true,
            'message' => sprintf('%s has been removed from the transfer market.', $valuable->name),
            'alert-type' => 'success',
        ];
    }

    /**
     * Purchase a listed valuable from a realm mate.
     */
    public function purchaseValuable(Dominion $buyer, Valuable $valuable): array
    {
        $this->guardLockedDominion($buyer);
        $this->guardActionsDuringTick($buyer);

        if (!$valuable->is_listed) {
            throw new GameException('That valuable is no longer available.');
        }

        $seller = $valuable->sourceDominion;
        if ($seller === null) {
            throw new GameException('Listing is broken — the seller no longer exists.');
        }

        if ($buyer->id === $seller->id) {
            throw new GameException('You cannot purchase your own listing.');
        }

        if ($buyer->realm_id !== $seller->realm_id) {
            throw new GameException('You can only purchase listings from your own realm mates.');
        }

        $price = $this->valuablesHelper->getTransferPrice($valuable);
        if ($buyer->resource_platinum < $price) {
            throw new GameException(sprintf(
                'You need %s platinum to purchase this listing.',
                number_format($price)
            ));
        }

        DB::transaction(function () use ($buyer, $seller, $valuable, $price) {
            $buyer->resource_platinum -= $price;
            $seller->resource_platinum += $price;

            $valuable->source_dominion_id = $buyer->id;
            $valuable->is_listed = false;
            $valuable->transferred = true;
            $valuable->status = Valuable::STATUS_DISCOVERED;
            $valuable->spies_assigned = null;
            $valuable->investigation_started_at = null;
            $valuable->investigation_ends_at = null;
            $valuable->save();

            $buyer->save(['event' => HistoryService::EVENT_ACTION_PURCHASE_VALUABLE]);
            $seller->save(['event' => HistoryService::EVENT_ACTION_TRANSFER_VALUABLE]);

            $this->notificationService
                ->queueNotification('valuable_purchased', [
                    'buyerDominionId' => $buyer->id,
                    'valuableName' => $valuable->name,
                    'transferPrice' => $price,
                ])
                ->sendNotifications($seller, 'irregular_dominion');
        });

        return [
            'success' => true,
            'message' => sprintf(
                'You purchased %s from %s for %s platinum.',
                $valuable->name,
                $seller->name,
                number_format($price)
            ),
            'alert-type' => 'success',
        ];
    }
}

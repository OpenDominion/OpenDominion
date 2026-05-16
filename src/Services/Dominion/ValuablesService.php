<?php

namespace OpenDominion\Services\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Helpers\ValuablesHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Models\Valuable;
use OpenDominion\Models\ValuablesTracking;

class ValuablesService
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var ValuablesHelper */
    protected $valuablesHelper;

    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->valuablesHelper = app(ValuablesHelper::class);
    }

    /**
     * Roll a passive discovery on top of an info op. Returns either an empty
     * string (no discovery) or a sentence to be appended to the op message.
     */
    public function attemptPassiveDiscovery(Dominion $attacker, Dominion $target, string $agent = 'spies'): string
    {
        if (!$this->canDiscoverFrom($attacker, $target)) {
            return '';
        }

        $tracking = $this->getOrCreateTracking($attacker, $target);
        $chance = $this->progressiveChance(ValuablesHelper::PASSIVE_DISCOVERY_CHANCE, $tracking->progress, ValuablesHelper::PASSIVE_PROGRESS_CHANCE_STEP);

        if (!random_chance($chance)) {
            $tracking->increment('progress', ValuablesHelper::PASSIVE_PROGRESS_INCREMENT);
            return '';
        }

        $valuable = $this->createValuable($attacker, $target);
        $phrase = $this->valuablesHelper->discoveryPhrase($valuable->rarity, $valuable->type);

        $tracking->progress = 0;
        $tracking->last_discovered_at = now();
        $tracking->save();

        return sprintf(
            ' Your %s have also discovered %s in the target\'s possession!',
            $agent,
            $phrase
        );
    }

    /**
     * Roll a valuables discovery. Returns the result envelope used by
     * EspionageActionService::performOperation().
     */
    public function attemptDiscovery(Dominion $attacker, Dominion $target): array
    {
        if (!$this->canDiscoverFrom($attacker, $target)) {
            return [
                'success' => false,
                'message' => 'Your spies could not infiltrate the target.',
                'alert-type' => 'warning',
            ];
        }

        $tracking = $this->getOrCreateTracking($attacker, $target);
        $chance = $this->progressiveChance(ValuablesHelper::SPY_OP_DISCOVERY_CHANCE, $tracking->progress);

        if (!random_chance($chance)) {
            $tracking->increment('progress', ValuablesHelper::SPY_OP_PROGRESS_INCREMENT);
            return [
                'success' => true,
                'message' => 'Your spies search the target carefully but find nothing of value.',
                'alert-type' => 'warning',
            ];
        }

        $valuable = $this->createValuable($attacker, $target);
        $phrase = $this->valuablesHelper->discoveryPhrase($valuable->rarity, $valuable->type);

        $tracking->progress = 0;
        $tracking->last_discovered_at = now();
        $tracking->save();

        return [
            'success' => true,
            'message' => sprintf(
                'Your spies have discovered %s in the target\'s possession: %s.',
                $phrase,
                $valuable->name
            ),
            'alert-type' => 'success',
        ];
    }

    /**
     * Returns whether the attacker is currently on cooldown for discovering
     * valuables from this specific target.
     */
    public function isOnCooldown(Dominion $attacker, Dominion $target): bool
    {
        $tracking = ValuablesTracking::query()
            ->where('round_id', $attacker->round_id)
            ->where('source_dominion_id', $attacker->id)
            ->where('target_dominion_id', $target->id)
            ->first();

        if ($tracking === null || $tracking->last_discovered_at === null) {
            return false;
        }

        return $tracking->last_discovered_at->diffInHours(now()) < ValuablesHelper::DISCOVERY_COOLDOWN_HOURS;
    }

    /**
     * Persist a brand-new valuable. Rolls rarity, type, name, transfer price.
     */
    protected function createValuable(Dominion $attacker, Dominion $target): Valuable
    {
        $rarity = $this->valuablesHelper->selectRarity($attacker, $target);
        $type = ValuablesHelper::TYPES[array_rand(ValuablesHelper::TYPES)];
        $name = $this->valuablesHelper->generateName($type, $rarity);
        $config = ValuablesHelper::getRarityConfig()[$rarity];
        $targetLand = $this->landCalculator->getTotalLand($target);
        $requiredSpyHours = (int) ceil($targetLand * $config['spy_hours_multiplier']);

        $valuable = new Valuable();
        $valuable->round_id = $attacker->round_id;
        $valuable->source_dominion_id = $attacker->id;
        $valuable->target_dominion_id = $target->id;
        $valuable->name = $name;
        $valuable->rarity = $rarity;
        $valuable->type = $type;
        $valuable->status = Valuable::STATUS_DISCOVERED;
        $valuable->required_spy_hours = $requiredSpyHours;
        $valuable->discovered_at = now();
        $valuable->save();

        return $valuable;
    }

    /**
     * Per-round tick pass: auto-complete investigations that have run their
     * timer, then expire anything past the 48-hour staleness window.
     */
    public function processValuables(Round $round): void
    {
        // Pass 1: auto-complete investigations whose timer has elapsed.
        Valuable::query()
            ->where('round_id', $round->id)
            ->where('status', Valuable::STATUS_INVESTIGATING)
            ->where('investigation_ends_at', '<=', now())
            ->get()
            ->each(function (Valuable $valuable) {
                $valuable->status = Valuable::STATUS_STOLEN;
                $valuable->stolen_at = now()->startOfHour();
                $valuable->is_listed = false;
                $valuable->save();
            });

        // Pass 2: expire anything past the staleness window that isn't resolved.
        Valuable::query()
            ->where('round_id', $round->id)
            ->whereNotIn('status', [
                Valuable::STATUS_SOLD,
                Valuable::STATUS_EXPIRED,
                Valuable::STATUS_FAILED,
                Valuable::STATUS_STOLEN,
            ])
            ->where('discovered_at', '<=', now()->subHours(ValuablesHelper::EXPIRATION_HOURS))
            ->get()
            ->each(function (Valuable $valuable) {
                $valuable->status = $valuable->status === Valuable::STATUS_INVESTIGATING
                    ? Valuable::STATUS_FAILED
                    : Valuable::STATUS_EXPIRED;
                $valuable->save();
            });
    }

    /**
     * Block discovery in cases where the underlying op shouldn't be eligible.
     * Cross-realm requirement is enforced upstream; this also enforces the
     * per-target cooldown so passive info ops respect it too.
     */
    protected function canDiscoverFrom(Dominion $attacker, Dominion $target): bool
    {
        if ($target->user_id === null) {
            return false;
        }
        if ($attacker->id === $target->id) {
            return false;
        }
        if ($attacker->round_id !== $target->round_id) {
            return false;
        }
        if ($this->isOnCooldown($attacker, $target)) {
            return false;
        }
        return true;
    }

    /**
     * Returns the effective discovery chance after applying progress points.
     */
    protected function progressiveChance(float $baseChance, int $progress, float $step = ValuablesHelper::PROGRESS_CHANCE_STEP): float
    {
        return min(
            ValuablesHelper::MAX_DISCOVERY_CHANCE,
            $baseChance + $progress * $step
        );
    }

    /**
     * Retrieves the tracking row for this attacker/target pair, creating it
     * if it does not yet exist.
     */
    protected function getOrCreateTracking(Dominion $attacker, Dominion $target): ValuablesTracking
    {
        return ValuablesTracking::firstOrCreate(
            [
                'round_id'           => $attacker->round_id,
                'source_dominion_id' => $attacker->id,
                'target_dominion_id' => $target->id,
            ],
            ['progress' => 0]
        );
    }
}

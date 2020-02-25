<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Realm\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class GovernmentActionService
{
    use DominionGuardsTrait;

    /** @var GovernmentService */
    protected $governmentService;

    /** @var NotificationService */
    protected $notificationService;

    /**
     * GovernmentActionService constructor.
     *
     * @param GovernmentService $governmentService
     */
    public function __construct(GovernmentService $governmentService, NotificationService $notificationService)
    {
        $this->governmentService = $governmentService;
        $this->notificationService = $notificationService;
    }

    public const WAR_DAYS_AFTER_ROUND_START = 5;

    /**
     * Casts a Dominion's vote for monarch.
     *
     * @param Dominion $dominion
     * @param int $monarch_id
     * @throws RuntimeException
     */
    public function voteForMonarch(Dominion $dominion, ?int $monarch_id)
    {
        $monarch = Dominion::find($monarch_id);
        if ($monarch == null) {
            throw new RuntimeException('Dominion not found.');
        }
        if ($dominion->realm != $monarch->realm) {
            throw new RuntimeException('You cannot vote for a monarch outside of your realm.');
        }

        $dominion->monarchy_vote_for_dominion_id = $monarch->id;
        $dominion->save();

        $this->governmentService->checkMonarchVotes($dominion->realm);
    }

    /**
     * Changes a Dominion's realm name.
     *
     * @param Dominion $dominion
     * @param string $name
     * @throws GameException
     */
    public function updateRealm(Dominion $dominion, ?string $motd, ?string $name)
    {
        if (!$dominion->isMonarch()) {
            throw new GameException('Only the monarch can make changes to their realm.');
        }

        if ($motd && strlen($motd) > 256) {
            throw new GameException('Realm messages are limited to 256 characters.');
        }

        if ($name && strlen($name) > 64) {
            throw new GameException('Realm names are limited to 64 characters.');
        }

        if ($motd) {
            $dominion->realm->motd = $motd;
            $dominion->realm->motd_updated_at = now();
        }
        if ($name) {
            $dominion->realm->name = $name;
        }
        $dominion->realm->save(['event' => HistoryService::EVENT_ACTION_REALM_UPDATED]);
    }

    /**
     * Declares war on target realm
     *
     * @param Dominion $dominion
     * @param int $realm_number
     * @throws GameException
     * @throws RuntimeException
     */
    public function declareWar(Dominion $dominion, int $realm_number)
    {
        $target = Realm::where(['round_id'=>$dominion->round_id, 'number'=>$realm_number])->first();
        if ($target == null || $dominion->realm->round_id != $target->round_id) {
            throw new RuntimeException('Realm not found.');
        }

        if (!$dominion->isMonarch()) {
            throw new GameException('Only the monarch can declare war.');
        }

        if ($dominion->realm->id == $target->id) {
            throw new RuntimeException('You cannot declare war against your own realm.');
        }

        if (!$this->governmentService->canDeclareWar($dominion->realm)) {
            throw new GameException('You cannot declare additional wars at this time.');
        }

        if (now()->diffInDays($dominion->round->start_date) < self::WAR_DAYS_AFTER_ROUND_START) {
            throw new GameException('You cannot declare war for the first five days of the round');
        }

        GameEvent::create([
            'round_id' => $dominion->realm->round_id,
            'source_type' => Realm::class,
            'source_id' => $dominion->realm->id,
            'target_type' => Realm::class,
            'target_id' => $target->id,
            'type' => 'war_declared',
            'data' => ['monarchDominionID' => $dominion->id],
        ]);

        $dominion->realm->war_realm_id = $target->id;
        $dominion->realm->war_active_at = now()->startOfHour()->addHours(GovernmentService::WAR_ACTIVE_WAIT_IN_HOURS);
        $dominion->realm->save(['event' => HistoryService::EVENT_ACTION_DECLARE_WAR]);

        // Send friendly notifications
        foreach ($dominion->realm->dominions as $friendlyDominion) {
            $this->notificationService
                ->queueNotification('declared_war_upon_enemy_realm', [
                    'sourceRealmId' => $dominion->realm->id,
                    'targetRealmId' => $target->id
                ])
                ->sendNotifications($friendlyDominion, 'irregular_realm');
        }

        // Send hostile notifications
        foreach ($target->dominions as $hostileDominion) {
            $this->notificationService
                ->queueNotification('enemy_realm_declared_war', [
                    'sourceRealmId' => $dominion->realm->id,
                    'targetRealmId' => $target->id
                ])
                ->sendNotifications($hostileDominion, 'irregular_realm');
        }
    }

    /**
     * Cancels the current war
     *
     * @param Dominion $dominion
     * @throws GameException
     */
    public function cancelWar(Dominion $dominion)
    {
        if (!$dominion->isMonarch()) {
            throw new GameException('Only the monarch can declare war.');
        }

        $hoursBeforeCancelWar = $this->governmentService->getHoursBeforeCancelWar($dominion->realm);
        if ($hoursBeforeCancelWar > 0) {
            throw new GameException("You cannot cancel this war for {$hoursBeforeCancelWar} hours.");
        }

        GameEvent::create([
            'round_id' => $dominion->realm->round_id,
            'source_type' => Realm::class,
            'source_id' => $dominion->realm->id,
            'target_type' => Realm::class,
            'target_id' => $dominion->realm->war_realm_id,
            'type' => 'war_canceled',
            'data' => ['monarchDominionID' => $dominion->id],
        ]);

        $dominion->realm->war_realm_id = null;
        $dominion->realm->war_active_at = null;
        $dominion->realm->save(['event' => HistoryService::EVENT_ACTION_CANCEL_WAR]);
    }
}

<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\GovernmentHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Realm\History;
use OpenDominion\Models\RealmWar;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Realm\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;
use OpenDominion\Traits\RealmGuardsTrait;
use RuntimeException;

class GovernmentActionService
{
    use DominionGuardsTrait;
    use RealmGuardsTrait;

    /** @var GovernmentHelper */
    protected $governmentHelper;

    /** @var GovernmentService */
    protected $governmentService;

    /** @var NotificationService */
    protected $notificationService;

    /**
     * GovernmentActionService constructor.
     *
     * @param GovernmentService $governmentService
     */
    public function __construct(
        GovernmentHelper $governmentHelper,
        GovernmentService $governmentService,
        NotificationService $notificationService
    )
    {
        $this->governmentHelper = $governmentHelper;
        $this->governmentService = $governmentService;
        $this->notificationService = $notificationService;
    }

    public const WAR_HOURS_AFTER_ROUND_START = 24 * 3;

    /**
     * Casts a Dominion's vote for monarch.
     *
     * @param Dominion $dominion
     * @param int $monarch_id
     * @throws RuntimeException
     */
    public function voteForMonarch(Dominion $dominion, ?int $monarch_id)
    {
        $this->guardLockedDominion($dominion);
        $this->guardGraveyardRealm($dominion->realm);

        $monarch = Dominion::find($monarch_id);
        if ($monarch == null) {
            throw new RuntimeException('Dominion not found.');
        }
        if ($dominion->realm_id != $monarch->realm_id) {
            throw new GameException('You cannot vote for a monarch outside of your realm.');
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
        $this->guardLockedDominion($dominion);
        $this->guardGraveyardRealm($dominion->realm);

        if (!$dominion->isMonarch() && !$dominion->isJester()) {
            throw new GameException('Only the monarch or court jester can make changes to their realm.');
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
     * Set appointments to the royal court
     *
     * @param Dominion $dominion
     * @param Dominion $appointee
     * @param string $roles
     * @throws GameException
     * @throws RuntimeException
     */
    public function setAppointments(Dominion $dominion, Dominion $appointee, string $role)
    {
        $this->guardLockedDominion($dominion);
        $this->guardGraveyardRealm($dominion->realm);

        if (!$dominion->isMonarch()) {
            throw new GameException('Only the monarch can make appointments.');
        }

        if ($appointee->id == $dominion->id) {
            // TODO: Should we clear appointments when monarchy changes?
            throw new GameException('You cannot appoint yourself to a seat on the royal court.');
        }

        $appointments = $this->governmentHelper->getCourtAppointments();
        // Validate role
        if (!$appointments->map(function ($item) { return $item['key']; })->contains($role)) {
            throw new GameException('Invalid role selection.');
        }

        $roleAttr = "{$role}_dominion_id";
        $currentDominionId = $dominion->realm->{$roleAttr};
        // No change
        if ($appointee->id == $currentDominionId) {
            throw new GameException('Appointee already holds that position.');
        }

        // Validate realm
        if ($dominion->realm_id !== $appointee->realm_id) {
            throw new GameException('You can only appoint dominions from your own realm.');
        }

        // Check cooldown
        $history = History::where('realm_id', $dominion->realm_id)
            ->where('created_at', '>', now()->subHours(72)->startOfHour())
            ->where('event', 'appointed ' . $role)
            ->orderByDesc('created_at')
            ->first();
        if ($history !== null) {
            $hoursRemaining = 72 - now()->diffInHours($history->created_at);
            throw new GameException("You cannot appoint a new {$role} for {$hoursRemaining} more hours.");
        }

        // Clear previous role
        foreach ($appointments as $appointment) {
            $attr = "{$appointment['key']}_dominion_id";
            if ($dominion->realm->{$attr} == $appointee->id) {
                throw new GameException('Each dominion can only hold one seat on the royal court.');
            }
        }

        $dominion->realm->{$roleAttr}= $appointee->id;
        $dominion->realm->save([
            'event' => 'appointed ' . $role,
            'monarch_dominion_id' => $dominion->id,
            $roleAttr => $appointee->id
        ]);
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
        $this->guardLockedDominion($dominion);
        $this->guardGraveyardRealm($dominion->realm);

        $target = Realm::where(['round_id'=>$dominion->round_id, 'number'=>$realm_number])->first();
        if ($target == null || $dominion->realm->round_id != $target->round_id) {
            throw new RuntimeException('Realm not found.');
        }

        $this->guardGraveyardRealm($target);

        if (!$dominion->isMonarch() && !$dominion->isGeneral()) {
            throw new GameException('Only the monarch or general can declare war.');
        }

        if ($dominion->realm->id == $target->id) {
            throw new RuntimeException('You cannot declare war against your own realm.');
        }

        if (!$this->governmentService->canDeclareWar($dominion->realm)) {
            throw new GameException('You cannot declare additional wars at this time.');
        }

        $recentlyCanceled = RealmWar::where([
            'source_realm_id' => $dominion->realm->id,
            'target_realm_id' => $target->id,
        ])->where('updated_at', '>', now()->startOfHour()->subHours(GovernmentService::WAR_REDECLARE_WAIT_IN_HOURS - 1))->get();

        if (!$recentlyCanceled->isEmpty()) {
            $lastCanceled = $recentlyCanceled->sortByDesc('updated_at')->first();

            $recentlyDeclared = RealmWar::where([
                'source_realm_id' => $target->id,
                'target_realm_id' => $dominion->realm->id,
            ])->where('created_at', '>', $lastCanceled->updated_at->startOfHour())->get();

            if ($recentlyDeclared->isEmpty()) {
                throw new GameException('You cannot redeclare war on the same realm within 48 hours of canceling.');
            }
        }

        if (now()->diffInHours($dominion->round->start_date) < self::WAR_HOURS_AFTER_ROUND_START) {
            throw new GameException('You cannot declare war for the first two days of the round.');
        }

        $war = RealmWar::create([
            'source_realm_id' => $dominion->realm->id,
            'source_realm_name' => $dominion->realm->name,
            'target_realm_id' => $target->id,
            'target_realm_name' => $target->name,
            'active_at' => now()->startOfHour()->addHours(GovernmentService::WAR_ACTIVE_WAIT_IN_HOURS),
        ]);

        $dominion->realm->save([
            'event' => HistoryService::EVENT_ACTION_DECLARE_WAR,
            'monarch_dominion_id' => $dominion->id,
            'war_id' => $war->id
        ]);

        GameEvent::create([
            'round_id' => $dominion->realm->round_id,
            'source_type' => Realm::class,
            'source_id' => $dominion->realm->id,
            'target_type' => RealmWar::class,
            'target_id' => $war->id,
            'type' => 'war_declared',
            'data' => ['monarchDominionID' => $dominion->id],
        ]);

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
        $this->guardLockedDominion($dominion);
        $this->guardGraveyardRealm($dominion->realm);

        if (!$dominion->isMonarch() && !$dominion->isGeneral()) {
            throw new GameException('Only the monarch or general can declare war.');
        }

        $war = $this->governmentService->getWarsEngaged($dominion->realm->warsOutgoing)->first();
        if ($war == null) {
            throw new GameException('Realm is not currently at war.');
        }

        $hoursBeforeCancelWar = $this->governmentService->getHoursBeforeCancelWar($war);
        if ($hoursBeforeCancelWar > 0) {
            throw new GameException("You cannot cancel this war for {$hoursBeforeCancelWar} hours.");
        }

        GameEvent::create([
            'round_id' => $dominion->realm->round_id,
            'source_type' => Realm::class,
            'source_id' => $dominion->realm->id,
            'target_type' => RealmWar::class,
            'target_id' => $war->id,
            'type' => 'war_canceled',
            'data' => ['monarchDominionID' => $dominion->id],
        ]);

        $war->inactive_at = now()->addHours(GovernmentService::WAR_INACTIVE_WAIT_IN_HOURS)->startOfHour();
        $war->save();

        $dominion->realm->save([
            'event' => HistoryService::EVENT_ACTION_CANCEL_WAR,
            'monarch_dominion_id' => $dominion->id,
            'war_id' => $war->id
        ]);
    }
}

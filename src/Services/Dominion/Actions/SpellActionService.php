<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Exception;
use Illuminate\Support\Str;
use LogicException;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Mappers\Dominion\InfoMapper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Spell;
use OpenDominion\Models\SpellPerkType;
use OpenDominion\Services\Dominion\BountyService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ValuablesService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

class SpellActionService
{
    use DominionGuardsTrait;

    /** @var BountyService */
    protected $bountyService;

    /** @var GovernmentService */
    protected $governmentService;

    /** @var GuardMembershipService */
    protected $guardMembershipService;

    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var InfoMapper */
    protected $infoMapper;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var OpsCalculator */
    protected $opsCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var QueueService */
    protected $queueService;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var SpellHelper */
    protected $spellHelper;

    /** @var ValuablesService */
    protected $valuablesService;

    /**
     * SpellActionService constructor.
     */
    public function __construct()
    {
        $this->bountyService = app(BountyService::class);
        $this->governmentService = app(GovernmentService::class);
        $this->guardMembershipService = app(GuardMembershipService::class);
        $this->heroCalculator = app(HeroCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->infoMapper = app(InfoMapper::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->opsCalculator = app(OpsCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->spellHelper = app(SpellHelper::class);
        $this->valuablesService = app(ValuablesService::class);
    }

    public const BLACK_OPS_HOURS_AFTER_ROUND_START = 24 * 3;

    /** @var string[] Improvements that Lightning Bolt damages and Repair Castle restores */
    public const REPAIRABLE_IMPROVEMENTS = ['science', 'keep', 'forges', 'walls'];

    /** @var int Hours before a repair is finished */
    public const REPAIR_HOURS = 1;

    /**
     * Casts a magic spell for a dominion, optionally aimed at another dominion.
     *
     * @param Dominion $dominion
     * @param string $spellKey
     * @param null|Dominion $target
     * @return array
     * @throws GameException
     * @throws LogicException
     */
    public function castSpell(Dominion $dominion, string $spellKey, Dominion|null $target = null): array
    {
        $this->guardLockedDominion($dominion);
        if ($target !== null) {
            $this->guardLockedDominion($target);
        }
        $this->guardActionsDuringTick($dominion);

        $spell = $this->spellHelper->getSpells($dominion->race)->get($spellKey);

        if ($spell == null) {
            throw new LogicException("Cannot cast unknown spell '{$spellKey}'");
        }

        if ($dominion->wizard_strength < 30) {
            throw new GameException("Your wizards do not have enough strength to cast {$spell->name}");
        }

        $manaCost = $this->spellCalculator->getManaCost($dominion, $spell);

        if ($dominion->resource_mana < $manaCost) {
            throw new GameException("You do not have enough mana to cast {$spell->name}");
        }

        if ($this->spellCalculator->isOnCooldown($dominion, $spell)) {
            throw new GameException("You can only cast {$spell->name} every {$spell->cooldown} hours");
        }

        if ($spell->hasPerk('invalid_protection') && !$dominion->protection_finished) {
            throw new GameException('You cannot cast this spell while under protection');
        }

        if ($spell->hasPerk('invalid_royal_guard') && $this->guardMembershipService->isRoyalGuardMember($dominion)) {
            throw new GameException('You cannot cast this spell while in the Royal Guard');
        }

        $resolveRequired = $spell->getPerkValue('requires_resolve');
        if ($resolveRequired && $dominion->resolve < $resolveRequired) {
            throw new GameException(sprintf(
                'Your dominion has not suffered enough to cast %s, which needs %s resolve and you have %s',
                $spell->name,
                number_format($resolveRequired),
                number_format($dominion->resolve)
            ));
        }

        if ($this->spellHelper->isShadowLeagueSpell($spell) && !$this->guardMembershipService->isBlackGuardMember($dominion)) {
            throw new GameException('You must be a member of the Shadow League to cast this spell');
        }

        // Status effects can block a spell from being cast at all (Fractured)
        if ($dominion->getSpellPerkValue("blocks_{$spell->key}", ['self', 'friendly', 'hostile', 'war', 'effect'])) {
            $blockingSpell = $dominion->spells->first(function ($activeSpell) use ($spell) {
                return $activeSpell->hasPerk("blocks_{$spell->key}");
            });
            throw new GameException(sprintf(
                'Your dominion is %s and cannot cast %s for another %s hours',
                $blockingSpell->name,
                $spell->name,
                $blockingSpell->pivot->duration
            ));
        }

        if ($this->spellHelper->isOffensiveSpell($spell)) {
            if ($target === null) {
                throw new GameException("You must select a target when casting offensive spell {$spell->name}");
            }

            if ($this->protectionService->isUnderProtection($dominion)) {
                throw new GameException('You cannot cast offensive spells while under protection');
            }

            if ($this->protectionService->isUnderProtection($target)) {
                throw new GameException('You cannot cast offensive spells to targets which are under protection');
            }

            if (!$this->rangeCalculator->isInRange($dominion, $target) && !in_array($target->id, $this->militaryCalculator->getRecentlyInvadedBy($dominion, 12))) {
                throw new GameException('You cannot cast offensive spells to targets outside of your range');
            }

            if ($dominion->round->id !== $target->round->id) {
                throw new GameException('Nice try, but you cannot cast spells cross-round');
            }

            if ($dominion->realm->id === $target->realm->id) {
                throw new GameException('Nice try, but you cannot cast spells on your realmies');
            }

            // Spells that strip a duration need that duration to be there
            foreach ($spell->perks as $perk) {
                if (!Str::startsWith($perk->key, 'reduce_duration_')) {
                    continue;
                }

                $affectedSpell = $this->spellHelper->getSpellByKey(str_replace('reduce_duration_', '', $perk->key));
                if ($affectedSpell !== null && !$this->spellCalculator->isSpellActive($target, $affectedSpell->key)) {
                    throw new GameException("{$target->name} is not protected by {$affectedSpell->name}, your wizards have nothing to unravel");
                }
            }
        }

        $result = null;
        $bountyMessage = '';
        $xpMessage = '';

        DB::transaction(function () use ($dominion, $target, $manaCost, $spell, &$result, &$bountyMessage, &$xpMessage) {
            $xpValue = 0;
            $wizardStrengthLost = $this->spellCalculator->getStrengthCost($dominion, $spell);
            if ($this->spellHelper->isInfoOpSpell($spell)) {
                $latestOp = $dominion->realm->infoOps()
                    ->where('target_dominion_id', $target->id)
                    ->where('type', $spell->key)
                    ->where('latest', true)
                    ->first();
            }

            if ($this->spellHelper->isSelfSpell($spell)) {
                $result = $this->castSelfSpell($dominion, $spell);
            } elseif ($this->spellHelper->isInfoOpSpell($spell)) {
                if ($this->guardMembershipService->isBlackGuardMember($dominion)) {
                    $wizardStrengthLost = 1;
                }
                $xpValue = $wizardStrengthLost;
                $result = $this->castInfoOpSpell($dominion, $spell, $target, $latestOp);
            } elseif ($this->spellHelper->isHostileSpell($spell)) {
                $xpValue = $wizardStrengthLost;
                $result = $this->castHostileSpell($dominion, $spell, $target);
                if (isset($result['damage']) && $result['damage'] == 0) {
                    $xpValue = 0;
                }
                $dominion->resetAbandonment();
            } elseif ($this->spellHelper->isFriendlySpell($spell)) {
                $xpValue = $wizardStrengthLost;
                $result = $this->castFriendlySpell($dominion, $spell, $target);
            } else {
                throw new LogicException("Unknown type for spell {$spell->key}");
            }

            // No XP for bots
            if ($target && $target->user_id == null) {
                $xpValue = 0;
            }

            // No XP for repeat ops
            if ($this->spellHelper->isInfoOpSpell($spell)) {
                if ($latestOp !== null && !$latestOp->isStale()) {
                    $xpValue = 0;
                }
            }

            // Amplify Magic
            if ($this->spellCalculator->isSpellActive($dominion, 'amplify_magic')) {
                if ($this->spellHelper->isSelfSpell($spell) && !$spell->cooldown) {
                    $activeSpell = $dominion->spells->where('key', 'amplify_magic')->first();
                    if ($activeSpell) {
                        $activeSpell->pivot->delete();
                    }
                }
            }

            // Delve into Shadow
            if ($target !== null) {
                $blackGuard = $this->guardMembershipService->isBlackGuardMember($dominion) && $this->guardMembershipService->isBlackGuardMember($target);
                $leagueWarSpell = $blackGuard && $this->spellHelper->isWarSpell($spell);
                $refundPerk = $dominion->getSpellPerkValue('spell_refund');
                if ($leagueWarSpell && $refundPerk && !$result['success']) {
                    $manaCost = (int) $manaCost * $refundPerk / 100;
                    $wizardStrengthLost = $wizardStrengthLost * $refundPerk / 100;
                }
            }

            $dominion->resource_mana -= $manaCost;
            $dominion->wizard_strength -= $wizardStrengthLost;

            if (!$this->spellHelper->isSelfSpell($spell)) {
                if ($result['success']) {
                    $xpGain = 0;
                    $cappedRawXp = 0;
                    if ($dominion->hero && $xpValue > 0) {
                        $rawXp = $this->heroCalculator->getRawExperienceGain($dominion, $xpValue, 'magic');
                        $remainingCap = max(0, HeroCalculator::DAILY_OPS_XP_CAP - $dominion->daily_xp);
                        $cappedRawXp = min($rawXp, $remainingCap);
                        $xpGain = $cappedRawXp * $this->heroCalculator->getExperienceMultiplier($dominion);
                    }
                    $dominion->stat_spell_success += 1;
                    // Bounty result (XP here is exempt from the daily cap)
                    if (isset($result['bounty']) && $result['bounty']) {
                        $bountyRewardString = '';
                        $rewards = $result['bounty'];
                        if (isset($rewards['xp']) && $rewards['xp']) {
                            $xpGain += $rewards['xp'];
                        }
                        if (isset($rewards['resource']) && isset($rewards['amount'])) {
                            $dominion->{$rewards['resource']} += $rewards['amount'];
                            $bountyRewardString = sprintf(' awarding %d %s', $rewards['amount'], dominion_attr_display($rewards['resource'], $rewards['amount']));
                        }
                        $bountyMessage = sprintf('You collected a bounty%s.', $bountyRewardString);
                    }
                    // Hero Experience
                    if ($dominion->hero && $xpGain) {
                        $dominion->hero->experience += $xpGain;
                        $dominion->hero->save();
                        $xpMessage = sprintf(' You gain %.3g XP.', $xpGain);
                    }
                    if ($cappedRawXp > 0) {
                        $dominion->daily_xp += $cappedRawXp;
                    }
                } else {
                    $dominion->stat_spell_failure += 1;
                }
            }

            if ($target == null) {
                $delta = [
                    'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                    'action' => $spell->key,
                ];
                if (isset($result['duration'])) {
                    $delta['queue'] = ['active_spells' => [$spell->key => $result['duration']]];
                }
                $dominion->save($delta);
            } else {
                $dominion->save([
                    'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                    'action' => $spell->key,
                    'target_dominion_id' => $target->id
                ]);

                if (Dominion::query()->whereKey($dominion->id)->value('wizard_strength') < 25) {
                    throw new GameException('Your wizards have run out of strength');
                }

                $target->save([
                    'event' => HistoryService::EVENT_ACTION_RECEIVE_SPELL,
                    'action' => $spell->key,
                    'source_dominion_id' => $dominion->id
                ]);
            }
        });

        if ($target !== null) {
            $this->rangeCalculator->checkGuardApplications($dominion, $target);
        }

        return [
                'message' => sprintf('%s %s %s', $result['message'], $bountyMessage, $xpMessage),
                'data' => [
                    'spell' => $spell->key,
                    'manaCost' => $manaCost,
                ],
                'redirect' =>
                    $this->spellHelper->isInfoOpSpell($spell) && $result['success']
                        ? route('dominion.op-center.show', $target->id)
                        : null,
            ] + $result;
    }

    /**
     * Casts a self spell for $dominion.
     *
     * @param Dominion $dominion
     * @param Spell $spell
     * @return array
     * @throws GameException
     * @throws LogicException
     */
    protected function castSelfSpell(Dominion $dominion, Spell $spell): array
    {
        $this->applySelfSpellPerks($dominion, $spell);

        if ($this->spellHelper->isInstantSpell($spell)) {
            $result = $this->applyInstantPerks($dominion, $dominion, $spell);

            return [
                'success' => true,
                'message' => $this->getInstantResultMessage($result['effects'], 'your dominion'),
                'damage' => $result['damage'],
            ];
        }

        $duration = $this->spellCalculator->getSpellDuration($dominion, $spell);

        // Wonders
        $duration += $dominion->getWonderPerkValue('spell_duration');

        $where = [
            'dominion_id' => $dominion->id,
            'spell_id' => $spell->id,
        ];
        $activeSpell = DominionSpell::firstWhere($where);
        $activeSpellDuration = $duration;

        if ($activeSpell !== null) {
            if ((int)$activeSpell->duration >= $duration || $spell->key == 'amplify_magic') {
                throw new GameException("Your wizards refused to recast {$spell->name}, since it is already at maximum duration.");
            }
            $activeSpellDuration = $activeSpell->duration;
            $activeSpell->where($where)->update(['duration' => $duration]);
        } else {
            DominionSpell::create([
                'dominion_id' => $dominion->id,
                'spell_id' => $spell->id,
                'duration' => $duration,
                'cast_by_dominion_id' => $dominion->id,
            ]);
        }

        return [
            'success' => true,
            'duration' => $activeSpellDuration,
            'message' => sprintf(
                'Your wizards cast the spell successfully, and it will continue to affect your dominion for the next %s hours.',
                $duration
            )
        ];
    }

    /**
     * Casts an info op spell for $dominion to $target.
     *
     * @param Dominion $dominion
     * @param Spell $spell
     * @param Dominion $target
     * @return array
     * @throws GameException
     * @throws Exception
     */
    protected function castInfoOpSpell(Dominion $dominion, Spell $spell, Dominion $target, ?InfoOp $latestOp = null): array
    {
        $selfWpa = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
        $targetWpa = $this->militaryCalculator->getWizardRatio($target, 'defense');

        // You need at least some positive WPA to cast info ops
        if ($selfWpa == 0) {
            // Don't reduce mana by throwing an exception here
            throw new GameException("Your wizard force is too weak to cast {$spell->name}. Please train more wizards.");
        }

        $successRate = $this->opsCalculator->infoOperationSuccessChance($selfWpa, $targetWpa, $dominion->wizard_strength, $target->wizard_strength);

        if (!random_chance($successRate)) {
            // Inform target that they repelled a info spell
            $sourceDominionId = $dominion->id;
            if ($dominion->hero !== null && $dominion->hero->getPerkValue('spell_fails_hide_identity')) {
                if (!$target->getSpellPerkValue('surreal_perception') && !$target->getWonderPerkValue('surreal_perception')) {
                    $sourceDominionId = null;
                }
            }

            $this->notificationService
                ->queueNotification('repelled_hostile_spell', [
                    'sourceDominionId' => $sourceDominionId,
                    'spellKey' => $spell->key,
                    'spellName' => $spell->name,
                    'unitsKilled' => '',
                ])
                ->sendNotifications($target, 'irregular_dominion');

            // Return here, thus completing the spell cast and reducing the caster's mana
            return [
                'success' => false,
                'message' => sprintf(
                    'The enemy wizards have repelled our %s attempt.',
                    $spell->name
                ),
                'alert-type' => 'warning',
            ];
        }

        $infoOp = new InfoOp([
            'source_realm_id' => $dominion->realm->id,
            'target_realm_id' => $target->realm->id,
            'type' => $spell->key,
            'source_dominion_id' => $dominion->id,
            'target_dominion_id' => $target->id,
        ]);

        switch ($spell->key) {
            case 'clear_sight':
                $infoOp->data = $this->infoMapper->mapStatus($target);
                break;

            case 'vision':
                $infoOp->data = [
                    'techs' => $this->infoMapper->mapTechs($target),
                    'heroes' => []
                ];
                break;

            case 'revelation':
                $infoOp->data = $this->infoMapper->mapSpells($target);
                break;

            case 'disclosure':
                $infoOp->data = $this->infoMapper->mapHeroes($target);
                break;

            case 'clairvoyance':
                $infoOp->data = [
                    'targetRealmId' => $target->realm->id
                ];
                break;

            default:
                throw new LogicException("Unknown info op spell {$spell->key}");
        }

        // Surreal Perception
        if ($target->getSpellPerkValue('surreal_perception') || $target->getWonderPerkValue('surreal_perception')) {
            $this->notificationService
                ->queueNotification('received_hostile_spell', [
                    'sourceDominionId' => $dominion->id,
                    'spellKey' => $spell->key,
                    'spellName' => $spell->name,
                ])
                ->sendNotifications($target, 'irregular_dominion');
        }

        $bountyRewards = $this->bountyService->collectBounty($dominion, $target, $spell->key);

        $infoOp->save();

        $discoveryMessage = '';
        if ($latestOp === null || $latestOp->isStale()) {
            $discoveryMessage = $this->valuablesService->attemptPassiveDiscovery($dominion, $target, 'wizards');
        }

        return [
            'success' => true,
            'message' => 'Your wizards cast the spell successfully, and a wealth of information appears before you.' . $discoveryMessage,
            'bounty' => $bountyRewards
        ];
    }

    /**
     * Casts a friendly spell for $dominion to $target.
     *
     * @param Dominion $dominion
     * @param Spell $spell
     * @param Dominion $target
     * @return array
     * @throws GameException
     * @throws LogicException
     */
    protected function castFriendlySpell(Dominion $dominion, Spell $spell, Dominion $target): array
    {
        if ($dominion->realm_id !== $target->realm_id) {
            throw new GameException('You cannot cast friendly spells on dominions outside of your realm');
        }

        if ($target->user_id == null) {
            throw new GameException('You cannot cast friendly spells on bots');
        }

        // Helping someone fight reaches only as far as an attack does
        if ($this->isLimitedSupportSpell($spell) && !$this->rangeCalculator->isInRange($dominion, $target)) {
            throw new GameException("{$target->name} is too far outside your range for your wizards to help them");
        }

        if ($spell->hasPerk('repair_improvements') && $this->getRepairableDamage($target) <= 0) {
            throw new GameException("{$target->name}'s castle is undamaged, your wizards have nothing to repair");
        }

        if ($spell->hasPerk('revive_peasants') && $target->peasants_killed <= 0) {
            throw new GameException("{$target->name} has no fallen peasants for your wizards to revive");
        }

        $castOnSelf = ($dominion->id === $target->id);

        if ($this->spellHelper->isInstantSpell($spell)) {
            $result = $this->applyInstantPerks($dominion, $target, $spell);

            // Inform target that they received a friendly spell
            if (!$castOnSelf) {
                $this->notificationService
                    ->queueNotification('received_friendly_spell', [
                        'sourceDominionId' => $dominion->id,
                        'spellKey' => $spell->key,
                        'spellName' => $spell->name,
                        'restored' => $result['restored'],
                    ])
                    ->sendNotifications($target, 'irregular_dominion');
            }

            return [
                'success' => true,
                'message' => $this->getInstantResultMessage($result['effects'], $castOnSelf ? 'your dominion' : 'your target'),
                'damage' => $result['damage'],
            ];
        }

        $activeSpell = $target->spells->find($spell->id);

        if ($activeSpell !== null) {
            throw new GameException("Your wizards refused to recast {$spell->name}, since it is already active");
        }

        $duration = $spell->duration;
        $duration += $dominion->getTechPerkValue('friendly_spell_duration');
        DominionSpell::create([
            'dominion_id' => $target->id,
            'spell_id' => $spell->id,
            'duration' => $duration,
            'cast_by_dominion_id' => $dominion->id,
        ]);

        // Inform target that they received a friendly spell
        if (!$castOnSelf) {
            $this->notificationService
                ->queueNotification('received_friendly_spell', [
                    'sourceDominionId' => $dominion->id,
                    'spellKey' => $spell->key,
                    'spellName' => $spell->name,
                ])
                ->sendNotifications($target, 'irregular_dominion');
        }

        return [
            'success' => true,
            'message' => sprintf(
                'Your wizards cast the spell successfully, and it will continue to affect %s for %s hours.',
                $castOnSelf ? 'your dominion' : 'your target',
                $duration,
            ),
            'duration' => $duration
        ];
    }

    /**
     * Casts a hostile spell for $dominion to $target.
     *
     * @param Dominion $dominion
     * @param Spell $spell
     * @param Dominion $target
     * @return array
     * @throws GameException
     * @throws LogicException
     */
    protected function castHostileSpell(Dominion $dominion, Spell $spell, Dominion $target): array
    {
        if ($dominion->round->hasOffensiveActionsDisabled()) {
            throw new GameException('Black ops have been disabled for the remainder of the round');
        }

        if ($dominion->round->start_date->diffInHours(now()) < self::BLACK_OPS_HOURS_AFTER_ROUND_START) {
            throw new GameException('You cannot perform black ops for the first three days of the round');
        }

        if ($dominion->realm_id == $target->realm_id) {
            throw new GameException('You cannot perform black ops on dominions in your realm');
        }

        if ($target->user_id == null) {
            throw new GameException('You cannot perform black ops on bots');
        }

        $warDeclared = $this->governmentService->isAtWar($dominion->realm, $target->realm);
        $mutualWarDeclared = $this->governmentService->isAtMutualWar($dominion->realm, $target->realm);
        $blackGuard = $this->guardMembershipService->isBlackGuardMember($dominion) && $this->guardMembershipService->isBlackGuardMember($target);
        if ($this->spellHelper->isWarSpell($spell)) {
            $recentlyInvaded = in_array($target->id, $this->militaryCalculator->getRecentlyInvadedBy($dominion, 12));
            if (!$warDeclared && !$recentlyInvaded) {
                if ($blackGuard) {
                    $this->guardMembershipService->checkLeaveApplication($dominion);
                } else {
                    throw new GameException("You cannot cast {$spell->name} outside of war.");
                }
            }
        }

        $selfWpa = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
        $targetWpa = $this->militaryCalculator->getWizardRatio($target, 'defense');

        // You need at least some positive WPA to cast black ops
        if ($selfWpa == 0) {
            // Don't reduce mana by throwing an exception here
            throw new GameException("Your wizard force is too weak to cast {$spell->name}. Please train more wizards.");
        }

        $successRate = $this->opsCalculator->blackOperationSuccessChance($selfWpa, $targetWpa, $dominion->wizard_strength, $target->wizard_strength);

        // Spells
        $successRate -= $target->getSpellPerkMultiplier('enemy_spell_chance');

        // Wonders
        $successRate *= (1 - $target->getWonderPerkMultiplier('enemy_spell_chance'));
        $failure = !random_chance($successRate);

        if ($failure) {
            list($unitsKilled, $unitsKilledString) = $this->handleLosses($dominion, $target, 'hostile');

            // Inform target that they repelled a hostile spell
            $sourceDominionId = $dominion->id;
            if ($dominion->hero !== null && $dominion->hero->getPerkValue('spell_fails_hide_identity')) {
                if (!$target->getSpellPerkValue('surreal_perception') && !$target->getWonderPerkValue('surreal_perception')) {
                    $sourceDominionId = null;
                }
            }

            $this->notificationService
                ->queueNotification('repelled_hostile_spell', [
                    'sourceDominionId' => $sourceDominionId,
                    'spellKey' => $spell->key,
                    'spellName' => $spell->name,
                    'unitsKilled' => $unitsKilledString,
                ])
                ->sendNotifications($target, 'irregular_dominion');

            if ($unitsKilledString) {
                $message = sprintf(
                    'The enemy wizards have repelled our %s attempt and managed to kill %s.',
                    $spell->name,
                    $unitsKilledString
                );
            } else {
                $message = sprintf(
                    'The enemy wizards have repelled our %s attempt.',
                    $spell->name
                );
            }

            return [
                'success' => false,
                'message' => $message,
                'alert-type' => 'warning',
            ];
        }

        $spellReflected = false;
        $spellReflect = $target->getSpellPerkValue('spell_reflect');
        if ($spellReflect) {
            $spellReflected = true;
            $friendlySpell = $target->spells->where('key', 'spell_reflect')->first();
            $reflectedBy = $friendlySpell->pivot->castByDominion;
            // Remove one-shot spell
            DominionSpell::where([
                'spell_id' => $friendlySpell->id,
                'dominion_id' => $target->id,
            ])->delete();
        }
        if ($spellReflected) {
            $protectedDominion = $target;
            $target = $dominion;
            $dominion = $reflectedBy;
            $dominion->stat_spells_reflected += 1;
            $target->stat_spells_deflected += 1;
        }

        if ($spell->duration > 0) {
            // Cast spell with duration (increased during war)
            $duration = $spell->duration;
            if ($mutualWarDeclared) {
                $duration += 4;
            } elseif ($warDeclared || $blackGuard) {
                $duration += 2;
            }
            $duration += $target->getTechPerkValue('enemy_spell_duration');
            $duration += $target->getSpellPerkValue('enemy_spell_duration');
            if ($target->hero !== null && $target->hero->getPerkValue('enemy_spell_duration')) {
                $duration += $target->hero->getPerkValue('enemy_spell_duration');
            }

            $activeSpell = $target->spells->find($spell->id);

            if ($activeSpell !== null) {
                $durationAdded = max(0, $duration - $activeSpell->pivot->duration);
                $activeSpell->pivot->duration += $durationAdded;
                $activeSpell->pivot->cast_by_dominion_id = $dominion->id;
                $activeSpell->pivot->save();
            } else {
                $durationAdded = $duration;
                DominionSpell::create([
                    'dominion_id' => $target->id,
                    'spell_id' => $spell->id,
                    'duration' => $duration,
                    'cast_by_dominion_id' => $dominion->id,
                ]);
            }

            // Update statistics
            if (isset($dominion->{"stat_{$spell->key}_hours"})) {
                $dominion->{"stat_{$spell->key}_hours"} += $durationAdded;
                $target->{"stat_{$spell->key}_hours_received"} += $durationAdded;
            }

            $damageDealtString = '';
            $warRewardsString = '';
            if (!$spellReflected && $durationAdded > 0 && (
                $this->spellHelper->isWarSpell($spell) ||
                ($this->spellHelper->isBlackOpSpell($spell) && ($warDeclared || $blackGuard))
            )) {
                $results = $this->handleWarResults($dominion, $target, $spell->key);
                $warRewardsString = $results['warRewards'];
                if ($results['damageDealt'] !== '') {
                    $damageDealtString = "Your target lost {$results['damageDealt']}.";
                }
            }

            // Surreal Perception
            $sourceDominionId = null;
            if ($target->getSpellPerkValue('surreal_perception') || $target->getWonderPerkValue('surreal_perception')) {
                $sourceDominionId = $dominion->id;
            }

            $this->notificationService
                ->queueNotification('received_hostile_spell', [
                    'sourceDominionId' => $sourceDominionId,
                    'spellKey' => $spell->key,
                    'spellName' => $spell->name,
                ])
                ->sendNotifications($target, 'irregular_dominion');

            if ($spellReflected) {
                // Record stats for caster
                if ($reflectedBy->isDirty()) {
                    $reflectedBy->save([
                        'event' => HistoryService::EVENT_ACTION_REFLECT_SPELL,
                        'action' => 'spell_reflect'
                    ]);
                }

                // Notification for spell reflection
                $this->notificationService
                    ->queueNotification('reflected_hostile_spell', [
                        'sourceDominionId' => $target->id,
                        'spellKey' => $spell->key,
                        'spellName' => $spell->name,
                        'protectedDominionId' => $protectedDominion->id,
                    ])
                    ->sendNotifications($dominion, 'irregular_dominion');

                $this->notificationService
                    ->queueNotification('reflected_hostile_spell', [
                        'sourceDominionId' => $target->id,
                        'spellKey' => $spell->key,
                        'spellName' => $spell->name,
                        'protectedDominionId' => $protectedDominion->id,
                    ])
                    ->sendNotifications($protectedDominion, 'irregular_dominion');

                return [
                    'success' => true,
                    'message' => sprintf(
                        'Your wizards cast the spell successfully, but it was reflected and it will now affect your dominion for an additional %s hours.',
                        $durationAdded
                    ),
                    'alert-type' => 'danger',
                    'reflected' => true
                ];
            } else {
                return [
                    'success' => true,
                    'message' => sprintf(
                        'Your wizards cast the spell successfully, and it will continue to affect your target for an additional %s hours. %s %s',
                        $durationAdded,
                        $damageDealtString,
                        $warRewardsString
                    ),
                    'damage' => $durationAdded
                ];
            }
        } else {
            // Cast spell instantly
            $damageMultiplier = $this->opsCalculator->getSpellDamageMultiplier($target, $spell->key, $dominion);
            $damageMultiplier *= $this->getOffensiveSizeMultiplier($dominion, $target);

            // A realmmate's Arcane Conduit lends its power to this one cast
            $empowerPerk = $spellReflected ? 0 : $dominion->getSpellPerkValue('empower', ['friendly']);
            if ($empowerPerk) {
                $damageMultiplier *= (1 + ($empowerPerk / 100));
            }
            $instantResult = $this->applyInstantPerks($dominion, $target, $spell, $damageMultiplier, $spellReflected);
            $damageDealt = $instantResult['effects'];
            $totalDamage = $instantResult['damage'];
            $applyBurning = $instantResult['applyBurning'];
            $instantStatusEffect = $instantResult['statusEffect'];

            // Combine lightning bolt damage into single string
            if ($spell->key === 'lightning_bolt') {
                // Combine lightning bold damage into single string
                $damageDealt = [sprintf('%s %s', number_format($totalDamage), dominion_attr_display('improvement', $totalDamage))];
            }

            // Backlash reflects a share of the damage before the hit hardens resolve
            $backlashString = '';
            if (!$spellReflected && $totalDamage > 0) {
                $backlashString = $this->applyBacklash($dominion, $target, $instantResult['attributes']);
                $target->resolve += $this->opsCalculator->getResolveGain($target, $mutualWarDeclared);
            }

            $warRewardsString = '';
            if (!$spellReflected && $totalDamage > 0 && (
                $this->spellHelper->isWarSpell($spell) ||
                ($this->spellHelper->isBlackOpSpell($spell) && ($warDeclared || $blackGuard))
            )) {
                $results = $this->handleWarResults($dominion, $target, $spell->key);
                $warRewardsString = $results['warRewards'];
                if ($results['damageDealt'] !== '') {
                    $damageDealt[] = $results['damageDealt'];
                }
            }

            // Surreal Perception
            $sourceDominionId = null;
            if ($target->getSpellPerkValue('surreal_perception') || $target->getWonderPerkValue('surreal_perception')) {
                $sourceDominionId = $dominion->id;
            }

            $empowerString = '';
            if ($empowerPerk && $totalDamage > 0) {
                $empowerString = $this->consumeEmpowerment($dominion);
            }

            $damageString = generate_sentence_from_array($damageDealt);

            // Apply Status Effects
            $statusEffect = $instantStatusEffect;
            $statusEffectString = '';
            if (!$spellReflected && $warDeclared && $statusEffect === null) {
                $statusEffect = $this->handleStatusEffects($dominion, $target, $spell, $applyBurning, $mutualWarDeclared);
            }
            if ($statusEffect !== null) {
                $statusEffectString = "You inflicted {$statusEffect}.";
            }

            $this->notificationService
                ->queueNotification('received_hostile_spell', [
                    'sourceDominionId' => $sourceDominionId,
                    'spellKey' => $spell->key,
                    'spellName' => $spell->name,
                    'damageString' => $damageString,
                    'statusEffect' => $statusEffect,
                ])
                ->sendNotifications($target, 'irregular_dominion');

            if ($spellReflected) {
                // Record stats for caster
                if ($reflectedBy->isDirty()) {
                    $reflectedBy->save([
                        'event' => HistoryService::EVENT_ACTION_REFLECT_SPELL,
                        'action' => 'spell_reflect'
                    ]);
                }

                // Notification for spell reflection
                $this->notificationService
                    ->queueNotification('reflected_hostile_spell', [
                        'sourceDominionId' => $target->id,
                        'spellKey' => $spell->key,
                        'spellName' => $spell->name,
                        'protectedDominionId' => $protectedDominion->id,
                    ])
                    ->sendNotifications($dominion, 'irregular_dominion');

                $this->notificationService
                    ->queueNotification('reflected_hostile_spell', [
                        'sourceDominionId' => $target->id,
                        'spellKey' => $spell->key,
                        'spellName' => $spell->name,
                        'protectedDominionId' => $protectedDominion->id,
                    ])
                    ->sendNotifications($protectedDominion, 'irregular_dominion');

                return [
                    'success' => true,
                    'message' => sprintf(
                        'Your wizards cast the spell successfully, but it was reflected and your dominion lost %s.',
                        $damageString
                    ),
                    'alert-type' => 'danger',
                    'reflected' => true
                ];
            } else {
                return [
                    'success' => true,
                    'message' => trim(sprintf(
                        'Your wizards cast the spell successfully, your target lost %s. %s %s %s %s',
                        $damageString,
                        $empowerString,
                        $statusEffectString,
                        $warRewardsString,
                        $backlashString
                    )),
                    'damage' => $totalDamage
                ];
            }
        }
    }

    /**
     * Returns the successful return message.
     *
     * Little e a s t e r e g g because I was bored.
     *
     * @param Dominion $dominion
     * @return string
     */
    protected function getReturnMessageString(Dominion $dominion): string
    {
        $wizards = $dominion->military_wizards;
        $archmages = $dominion->military_archmages;
        $spies = $dominion->military_spies;

        if (($wizards === 0) && ($archmages === 0)) {
            return 'You cast %s at a cost of %s mana.';
        }

        if ($wizards === 0) {
            if ($archmages > 1) {
                return 'Your archmages successfully cast %s at a cost of %s mana.';
            }

            $thoughts = [
                'mumbles something about being the most powerful sorceress in the dominion is a lonely job, "but somebody\'s got to do it"',
                'mumbles something about the food being quite delicious',
                'feels like a higher spiritual entity is watching her',
                'winks at you',
            ];

            if ($this->queueService->getTrainingQueueTotalByResource($dominion, 'military_wizards') > 0) {
                $thoughts[] = 'carefully observes the trainee wizards';
            } else {
                $thoughts[] = 'mumbles something about the lack of student wizards to teach';
            }

            if ($this->queueService->getTrainingQueueTotalByResource($dominion, 'military_archmages') > 0) {
                $thoughts[] = 'mumbles something about being a bit sad because she probably won\'t be the single most powerful sorceress in the dominion anymore';
                $thoughts[] = 'mumbles something about looking forward to discuss the secrets of arcane knowledge with her future peers';
            } else {
                $thoughts[] = 'mumbles something about not having enough peers to properly conduct her studies';
                $thoughts[] = 'mumbles something about feeling a bit lonely';
            }

            return ('Your archmage successfully casts %s at a cost of %s mana. In addition, she ' . $thoughts[array_rand($thoughts)] . '.');
        }

        if ($archmages === 0) {
            if ($wizards > 1) {
                return 'Your wizards successfully cast %s at a cost of %s mana.';
            }

            $thoughts = [
                'mumbles something about the food being very tasty',
                'has the feeling that an omnipotent being is watching him',
            ];

            if ($this->queueService->getTrainingQueueTotalByResource($dominion, 'military_wizards') > 0) {
                $thoughts[] = 'mumbles something about being delighted by the new wizard trainees so he won\'t be lonely anymore';
            } else {
                $thoughts[] = 'mumbles something about not having enough peers to properly conduct his studies';
                $thoughts[] = 'mumbles something about feeling a bit lonely';
            }

            if ($this->queueService->getTrainingQueueTotalByResource($dominion, 'military_archmages') > 0) {
                $thoughts[] = 'mumbles something about looking forward to his future teacher';
            } else {
                $thoughts[] = 'mumbles something about not having an archmage master to study under';
            }

            if ($spies === 1) {
                $thoughts[] = 'mumbles something about fancying that spy lady';
            } elseif ($spies > 1) {
                $thoughts[] = 'mumbles something about thinking your spies are complotting against him';
            }

            return ('Your wizard successfully casts %s at a cost of %s mana. In addition, he ' . $thoughts[array_rand($thoughts)] . '.');
        }

        if (($wizards === 1) && ($archmages === 1)) {
            $strings = [
                'Your wizards successfully cast %s at a cost of %s mana.',
                'Your wizard and archmage successfully cast %s together in harmony at a cost of %s mana. It was glorious to behold.',
                'Your wizard watches in awe while his teacher archmage blissfully casts %s at a cost of %s mana.',
                'Your archmage facepalms as she observes her wizard student almost failing to cast %s at a cost of %s mana.',
                'Your wizard successfully casts %s at a cost of %s mana, while his teacher archmage watches him with pride.',
            ];

            return $strings[array_rand($strings)];
        }

        if (($wizards === 1) && ($archmages > 1)) {
            $strings = [
                'Your wizards successfully cast %s at a cost of %s mana.',
                'Your wizard was sleeping, so your archmages successfully cast %s at a cost of %s mana.',
                'Your wizard watches carefully while your archmages successfully cast %s at a cost of %s mana.',
            ];

            return $strings[array_rand($strings)];
        }

        if (($wizards > 1) && ($archmages === 1)) {
            $strings = [
                'Your wizards successfully cast %s at a cost of %s mana.',
                'Your archmage found herself lost in her study books, so your wizards successfully cast %s at a cost of %s mana.',
            ];

            return $strings[array_rand($strings)];
        }

        return 'Your wizards successfully cast %s at a cost of %s mana.';
    }

    /**
     * Applies the perks a self spell resolves on the caster regardless of
     * whether the spell is instant or has a duration.
     *
     * @param Dominion $dominion
     * @param Spell $spell
     * @return void
     */
    protected function applySelfSpellPerks(Dominion $dominion, Spell $spell): void
    {
        foreach ($spell->perks as $perk) {
            if (Str::startsWith($perk->key, 'sacrifice_')) {
                $attr = str_replace('sacrifice_', '', $perk->key);
                $percentage = $perk->pivot->value / 100;
                $dominion->{$attr} -= round($dominion->{$attr} * $percentage);
            }

            if (Str::startsWith($perk->key, 'cancels_')) {
                $spellToCancel = str_replace('cancels_', '', $perk->key);
                $activeSpells = $dominion->spells->keyBy('key');
                if ($activeSpells->has($spellToCancel)) {
                    $activeSpells->get($spellToCancel)->pivot->delete();
                }
            }
        }
    }

    /**
     * Returns the message for an instant spell cast on the caster or a realmmate.
     *
     * @param array $effects
     * @param string $affected
     * @return string
     */
    protected function getInstantResultMessage(array $effects, string $affected): string
    {
        if ($effects === []) {
            return 'Your wizards cast the spell successfully.';
        }

        return sprintf(
            'Your wizards cast the spell successfully, affecting %s: %s.',
            $affected,
            generate_sentence_from_array($effects)
        );
    }

    /**
     * Applies the instant effects of a spell to $target.
     *
     * Shared by every category so that hostile, war, friendly, and self spells
     * can all have instant versions. Effects are declared as perks:
     *
     * - `destroy_<attribute>` removes a percentage of the target's attribute.
     * - `convert_<attribute>_to_<attribute>` moves it to another attribute, or
     *   to the caster when the destination is prefixed with `self_`.
     *
     * New verbs belong in the chain below, alongside the two above. Perks that
     * are not instant effects (`apply_`, `immune_`, `sacrifice_`, `cancels_`,
     * `war_cancels`) are skipped here and handled by the caller.
     *
     * @param Dominion $dominion The caster
     * @param Dominion $target The affected dominion, the caster for self spells
     * @param Spell $spell
     * @param float $damageMultiplier
     * @param bool $spellReflected
     * @return array{damage: int, effects: array, attributes: array<string, int>, restored: array{peasants?: int, improvements?: int}, applyBurning: bool, statusEffect: ?string}
     * @throws GameException
     */
    protected function applyInstantPerks(
        Dominion $dominion,
        Dominion $target,
        Spell $spell,
        float $damageMultiplier = 1,
        bool $spellReflected = false
    ): array
    {
        $damageDealt = [];
        $damageByAttribute = [];
        $restored = [];
        $totalDamage = 0;
        $applyBurning = false;
        $statusEffect = null;

        foreach ($spell->perks as $perk) {
            // Perks that shape the spell rather than being an effect of their own
            $perksToIgnore = collect(['war_cancels', 'temporary_damage']);
            if ($perk->key === 'revive_peasants') {
                $revived = $this->revivePeasants($target, (float)$perk->pivot->value * $this->getSupportMultiplier($dominion, $target));

                if ($revived > 0) {
                    $totalDamage += $revived;
                    $restored['peasants'] = $revived;
                    $damageDealt[] = sprintf('%s %s', number_format($revived), dominion_attr_display('peasants', $revived));
                }
                continue;
            } elseif ($perk->key === 'repair_improvements') {
                $repairs = $this->repairImprovements($target, (float)$perk->pivot->value * $this->getSupportMultiplier($dominion, $target));

                if ($repairs['repaired'] > 0) {
                    $totalDamage += $repairs['repaired'];
                    $restored['improvements'] = $repairs['repaired'];
                    $damageDealt[] = sprintf(
                        '%s %s underway%s',
                        number_format($repairs['repaired']),
                        dominion_attr_display('improvement', $repairs['repaired']),
                        $repairs['delayed'] ? ', slowed by Ruin' : ''
                    );
                }
                continue;
            } elseif (Str::startsWith($perk->key, 'reduce_duration_')) {
                $affected = $this->reduceSpellDuration(
                    $dominion,
                    $target,
                    str_replace('reduce_duration_', '', $perk->key),
                    (int)$perk->pivot->value,
                    $spell
                );

                if ($affected === null) {
                    continue;
                }

                $totalDamage += $affected['hours'];
                $damageDealt[] = $affected['effect'];
                if ($affected['statusEffect'] !== null) {
                    $statusEffect = $affected['statusEffect'];
                }
                continue;
            } elseif (Str::startsWith($perk->key, 'destroy_')) {
                $attr = str_replace('destroy_', '', $perk->key);
                $convertAttr = null;
            } elseif (Str::startsWith($perk->key, 'convert_')) {
                $components = Str::of($perk->key)->replace('convert_', '')->explode('_to_');
                list($attr, $convertAttr) = $components;
            } elseif ($perksToIgnore->contains($perk->key) || Str::startsWith($perk->key, 'apply_') || Str::startsWith($perk->key, 'immune_')) {
                continue;
            } else {
                throw new GameException("Unrecognized perk {$perk->key}.");
            }

            // Protection only applies to spells cast at an enemy
            $hostile = $this->spellHelper->isHostileSpell($spell);

            $attrValue = $target->{$attr};
            if ($hostile && $attr == 'peasants') {
                if ($attrValue == 0) {
                    throw new GameException("Your wizards refused to cast {$spell->name}, since there is nothing left to burn.");
                }
            } elseif ($hostile && Str::startsWith($attr, 'improvement_')) {
                $improvementsVulnerable = $this->opsCalculator->getImprovementsVulnerable($target);
                if ($improvementsVulnerable == 0) {
                    throw new GameException("Your wizards refused to cast {$spell->name}, since there is nothing left to destroy.");
                }
            }

            // Cap damage reduction at 80%
            $baseDamage = $perk->pivot->value / 100;
            $damage = rceil($attrValue * $baseDamage * $damageMultiplier);

            // Damage that grows back on its own is queued rather than recorded,
            // so there is nothing for a realmmate to repair or revive
            $temporaryHours = (int)$spell->getPerkValue('temporary_damage');

            if ($temporaryHours > 0 && $damage > 0) {
                $this->queueService->queueResources(
                    'operations',
                    $target,
                    [$attr => $damage],
                    $temporaryHours
                );
            } else {
                // Peasants killed can be revived until they grow back on their own
                if ($hostile && $attr == 'peasants' && $damage > 0) {
                    $target->peasants_killed += $damage;
                }

                // Permanent improvement damage can be repaired later
                if (Str::startsWith($attr, 'improvement_') && $damage > 0) {
                    $improvement = str_replace('improvement_', '', $attr);
                    if (in_array($improvement, static::REPAIRABLE_IMPROVEMENTS, true)) {
                        $ledger = "improvement_damage_{$improvement}";
                        $target->{$ledger} = (int)$target->{$ledger} + $damage;
                    }
                }
            }

            // Temporary lightning damage
            if (Str::startsWith($attr, 'improvement_') && $damage > 0) {
                $lightningStormSpell = $target->spells->where('key', 'lightning_storm')->first();
                if ($lightningStormSpell !== null) {
                    $lightningPerkValue = $target->getSpellPerkValue('lightning_storm', ['effect']) / 100;
                    $amount = round($damage * $lightningPerkValue);
                    $duration = $lightningStormSpell->pivot->duration;
                    if ($amount > 0) {
                        $this->queueService->queueResources(
                            'operations',
                            $target,
                            [$attr => $amount],
                            $duration
                        );
                        $damage += $amount;
                    }
                }
            }

            // Immortal Wizards
            if ($attr == 'military_wizards' && $target->race->getPerkValue('immortal_wizards') != 0) {
                $damage = 0;
            }

            $target->{$attr} -= $damage;
            if ($convertAttr !== null) {
                if (Str::startsWith($convertAttr, 'self_') && !$spellReflected) {
                    $convertAttr = str_replace('self_', '', $convertAttr);
                    $converted = $damage;
                    if (Str::startsWith($convertAttr, 'military_')) {
                        // Military Conversions
                        $converted = round($damage * 0.05);
                    }
                    $this->queueService->queueResources(
                        'invasion',
                        $dominion,
                        [$convertAttr => $converted],
                        12
                    );
                } else {
                    $target->{$convertAttr} += $damage;
                }
            }

            $totalDamage += $damage;
            $damageByAttribute[$attr] = ($damageByAttribute[$attr] ?? 0) + $damage;
            $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attr, $damage));

            // Update statistics
            if (isset($dominion->{"stat_{$spell->key}_damage"})) {
                // Only count peasants killed by fireball
                if (!($spell->key == 'fireball' && $attr == 'resource_food')) {
                    $dominion->{"stat_{$spell->key}_damage"} += $damage;
                    $target->{"stat_{$spell->key}_damage_received"} += $damage;
                }
            }
        }

        return [
            'damage' => $totalDamage,
            'effects' => $damageDealt,
            'attributes' => $damageByAttribute,
            'restored' => $restored,
            'applyBurning' => $applyBurning,
            'statusEffect' => $statusEffect,
        ];
    }

    /**
     * Spends the empowerment a realmmate lent to this dominion.
     *
     * Like Spell Reflect, the spell is a single use: it is removed once the
     * cast it was holding has landed.
     *
     * @param Dominion $dominion
     * @return string
     */
    protected function consumeEmpowerment(Dominion $dominion): string
    {
        $empowerSpell = $dominion->spells->first(function ($activeSpell) {
            return $activeSpell->hasPerk('empower');
        });

        if ($empowerSpell === null) {
            return '';
        }

        DominionSpell::where([
            'dominion_id' => $dominion->id,
            'spell_id' => $empowerSpell->id,
        ])->delete();

        $dominion->unsetRelation('spells');

        return sprintf('%s carried the cast and has faded.', $empowerSpell->name);
    }

    /**
     * Returns how much of an instant spell's damage survives the gap in size
     * between caster and target.
     *
     * Damage is a share of what the target owns while mana is a share of what
     * the caster owns, so striking a larger dominion is otherwise the cheapest
     * damage in the game. Scaling by size makes a realm's pressure on a target
     * proportional to the realm's own size. Striking a smaller dominion is
     * unaffected, since guard ranges already limit how far down anyone reaches.
     *
     * The realm's Warmage is exempt: they are the appointed answer to a
     * dominion larger than anyone else can meaningfully hurt.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @return float
     */
    protected function getOffensiveSizeMultiplier(Dominion $dominion, Dominion $target): float
    {
        if ($dominion->isMage()) {
            return 1;
        }

        $targetLand = $this->landCalculator->getTotalLand($target);

        if ($targetLand <= 0) {
            return 1;
        }

        return min(1, $this->landCalculator->getTotalLand($dominion) / $targetLand);
    }

    /**
     * Returns whether a spell undoes damage a dominion has taken or lends them
     * power to deal some.
     *
     * These are held to the same range rules as the spells they answer, so a
     * realm cannot mend or arm a dominion that nobody could reach.
     *
     * @param Spell $spell
     * @return bool
     */
    protected function isLimitedSupportSpell(Spell $spell): bool
    {
        return (
            $spell->hasPerk('repair_improvements') ||
            $spell->hasPerk('revive_peasants') ||
            $spell->hasPerk('empower')
        );
    }

    /**
     * Returns how much of a recovery spell survives the gap in size between
     * caster and target.
     *
     * A dominion can only fully undo damage to someone its own size or smaller,
     * so a realm cannot mend its largest dominion with its smallest ones.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @return float
     */
    protected function getSupportMultiplier(Dominion $dominion, Dominion $target): float
    {
        if ($dominion->id === $target->id) {
            return 1;
        }

        $targetLand = $this->landCalculator->getTotalLand($target);

        if ($targetLand <= 0) {
            return 1;
        }

        return min(1, $this->landCalculator->getTotalLand($dominion) / $targetLand);
    }

    /**
     * Reflects a share of an instant spell's damage back at the caster.
     *
     * The share comes from the target's resolve, which builds as they are hit.
     * It does not spare the target anything: the same damage lands on them, the
     * caster simply pays for it as well. Reflected losses are recorded against
     * the caster the same way, so their own realm can repair and revive them.
     *
     * @param Dominion $dominion The caster, who takes the reflected damage
     * @param Dominion $target
     * @param array $damageByAttribute
     * @return string
     */
    protected function applyBacklash(Dominion $dominion, Dominion $target, array $damageByAttribute): string
    {
        $multiplier = $this->opsCalculator->getBacklashMultiplier($target);

        if ($multiplier <= 0) {
            return '';
        }

        $reflected = [];
        foreach ($damageByAttribute as $attr => $damage) {
            $amount = (int)rfloor($damage * $multiplier);
            $amount = min($amount, (int)$dominion->{$attr});

            if ($amount <= 0) {
                continue;
            }

            $dominion->{$attr} -= $amount;

            if ($attr === 'peasants') {
                $dominion->peasants_killed += $amount;
            }

            if (Str::startsWith($attr, 'improvement_')) {
                $improvement = str_replace('improvement_', '', $attr);
                if (in_array($improvement, static::REPAIRABLE_IMPROVEMENTS, true)) {
                    $ledger = "improvement_damage_{$improvement}";
                    $dominion->{$ledger} = (int)$dominion->{$ledger} + $amount;
                }
            }

            $reflected[] = sprintf('%s %s', number_format($amount), dominion_attr_display($attr, $amount));
        }

        if ($reflected === []) {
            return '';
        }

        return sprintf('The spell rebounded on your dominion, costing you %s.', generate_sentence_from_array($reflected));
    }

    /**
     * Revives a percentage of the peasants Fireball has killed and that have
     * not yet grown back on their own.
     *
     * Capped by the room left under the target's maximum population, so a
     * dominion that has since lost land cannot be pushed over its own ceiling.
     *
     * @param Dominion $target
     * @param float $percentage
     * @return int
     */
    protected function revivePeasants(Dominion $target, float $percentage): int
    {
        $peasantsKilled = (int)$target->peasants_killed;

        if ($peasantsKilled <= 0) {
            return 0;
        }

        $roomForPeasants = max(0, $this->populationCalculator->getMaxPeasantPopulation($target) - $target->peasants);
        $revived = min($peasantsKilled, (int)rceil($peasantsKilled * $percentage / 100), $roomForPeasants);

        $target->peasants += $revived;
        $target->peasants_killed -= $revived;

        return $revived;
    }

    /**
     * Repairs a percentage of the castle damage recorded against $target.
     *
     * Each improvement is repaired in proportion to the damage it took, so a
     * dominion can never be repaired past what it invested. Repairs land on the
     * next tick rather than immediately, so that an attacker who scouts a castle
     * knows what they are invading into for the rest of the hour. The damage is
     * struck from the ledger as the repair is queued, so repeat casts cannot
     * claim the same damage twice.
     *
     * @param Dominion $target
     * @param float $percentage
     * @return array{repaired: int, delayed: bool}
     */
    protected function repairImprovements(Dominion $target, float $percentage): array
    {
        // Ruin slows repairs to the improvements that decide invasions
        $repairDelay = (int)$target->getSpellPerkValue('repair_delay', ['hostile', 'war', 'effect']);

        $repaired = 0;
        $delayed = false;
        $repairs = [];
        $delayedRepairs = [];

        foreach (static::REPAIRABLE_IMPROVEMENTS as $improvement) {
            $damageTaken = (int)$target->{"improvement_damage_{$improvement}"};

            if ($damageTaken <= 0) {
                continue;
            }

            $amount = min($damageTaken, (int)rceil($damageTaken * $percentage / 100));
            $target->{"improvement_damage_{$improvement}"} -= $amount;
            $repaired += $amount;

            if ($repairDelay > static::REPAIR_HOURS) {
                $delayedRepairs["improvement_{$improvement}"] = $amount;
                $delayed = true;
            } else {
                $repairs["improvement_{$improvement}"] = $amount;
            }
        }

        if ($repairs !== []) {
            $this->queueService->queueResources('operations', $target, $repairs, static::REPAIR_HOURS);
        }

        if ($delayedRepairs !== []) {
            $this->queueService->queueResources('operations', $target, $delayedRepairs, $repairDelay);
        }

        return [
            'repaired' => $repaired,
            'delayed' => $delayed,
        ];
    }

    /**
     * Returns the total castle damage recorded against $dominion.
     *
     * @param Dominion $dominion
     * @return int
     */
    protected function getRepairableDamage(Dominion $dominion): int
    {
        $damage = 0;

        foreach (static::REPAIRABLE_IMPROVEMENTS as $improvement) {
            $damage += (int)$dominion->{"improvement_damage_{$improvement}"};
        }

        return $damage;
    }

    /**
     * Strips hours from a spell active on $target.
     *
     * A spell broken this way leaves behind whatever status effect the spell
     * doing the breaking carries. Running out on its own leaves nothing: only a
     * ward that was torn down leaves the dominion Fractured.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $spellKey
     * @param int $hours
     * @param Spell $castSpell The spell doing the breaking
     * @return array{hours: int, effect: string, statusEffect: string|null}|null
     */
    protected function reduceSpellDuration(Dominion $dominion, Dominion $target, string $spellKey, int $hours, Spell $castSpell): ?array
    {
        $activeSpell = $target->spells->where('key', $spellKey)->first();

        if ($activeSpell === null) {
            return null;
        }

        $hoursRemoved = min($hours, (int)$activeSpell->pivot->duration);
        $remaining = (int)$activeSpell->pivot->duration - $hoursRemoved;
        $statusEffect = null;

        if ($remaining > 0) {
            $activeSpell->pivot->duration = $remaining;
            $activeSpell->pivot->save();
        } else {
            $statusEffectSpell = $this->getAppliedEffect($castSpell);

            DominionSpell::where([
                'dominion_id' => $target->id,
                'spell_id' => $activeSpell->id,
            ])->delete();

            if ($statusEffectSpell !== null) {
                DominionSpell::create([
                    'dominion_id' => $target->id,
                    'spell_id' => $statusEffectSpell->id,
                    'duration' => $statusEffectSpell->duration,
                    'cast_by_dominion_id' => $dominion->id,
                ]);
                $statusEffect = $statusEffectSpell->name;
            }

            $target->unsetRelation('spells');
        }

        return [
            'hours' => $hoursRemoved,
            'effect' => sprintf('%s hours of %s', number_format($hoursRemoved), $activeSpell->name),
            'statusEffect' => $statusEffect,
        ];
    }

    /**
     * Returns the status effect a spell applies, if any.
     *
     * @param Spell $spell
     * @return Spell|null
     */
    protected function getAppliedEffect(Spell $spell): ?Spell
    {
        foreach ($spell->perks as $perk) {
            if (Str::startsWith($perk->key, 'apply_')) {
                return $this->spellHelper->getSpellByKey(str_replace('apply_', '', $perk->key));
            }
        }

        return null;
    }

    /**
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return array
     * @throws Exception
     */
    protected function handleLosses(Dominion $dominion, Dominion $target, string $type): array
    {
        $wizardsKilledPercentage = $this->opsCalculator->getWizardLosses($dominion, $target, $type);
        $archmagesKilledPercentage = $this->opsCalculator->getArchmageLosses($dominion, $target, $type);
        // Cap losses by land size
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        $wizardsKilledCap = $totalLand * 0.02;
        $archmagesKilledCap = $totalLand * 0.002;
        $unitsKilledCap = $totalLand * 0.01;

        $wizardsKilledModifier = 1;
        // Losses re-queued in Black Guard
        $blackGuard = $this->guardMembershipService->isBlackGuardMember($dominion) && $this->guardMembershipService->isBlackGuardMember($target);

        $unitsKilled = [];
        $wizardsKilled = (int)rfloor($dominion->military_wizards * $wizardsKilledPercentage);
        $wizardsKilled = round(min($wizardsKilled, $wizardsKilledCap) * $wizardsKilledModifier);
        $archmagesKilled = (int)rfloor($dominion->military_archmages * $archmagesKilledPercentage);
        $archmagesKilled = round(min($archmagesKilled, $archmagesKilledCap) * $wizardsKilledModifier);

        // Check for immortal wizards
        if ($dominion->race->getPerkValue('immortal_wizards') != 0) {
            $wizardsKilled = 0;
            $archmagesKilled = 0;
        }

        if ($wizardsKilled > 0) {
            $unitsKilled['wizards'] = $wizardsKilled;
            $dominion->military_wizards -= $wizardsKilled;
            if ($blackGuard && $wizardsKilled > 1) {
                $this->queueService->queueResources('training', $dominion, ['military_wizards' => rfloor(0.75 * $wizardsKilled)]);
            }
        }

        if ($archmagesKilled > 0) {
            $unitsKilled['archmages'] = $archmagesKilled;
            $dominion->military_archmages -= $archmagesKilled;
            if ($blackGuard && $archmagesKilled > 1) {
                $this->queueService->queueResources('training', $dominion, ['military_archmages' => rfloor(0.75 * $archmagesKilled)]);
            }
        }

        foreach ($dominion->race->units as $unit) {
            if ($unit->getPerkValue('counts_as_wizard')) {
                $unitKilledMultiplier = ((float)$unit->getPerkValue('counts_as_wizard') / 2) * $wizardsKilledPercentage;
                $unitKilled = (int)rfloor($dominion->{"military_unit{$unit->slot}"} * $unitKilledMultiplier);
                $unitKilled = round(min($unitKilled, $unitsKilledCap) * $wizardsKilledModifier);
                if ($unitKilled > 0) {
                    $unitsKilled[strtolower($unit->name)] = $unitKilled;
                    $dominion->{"military_unit{$unit->slot}"} -= $unitKilled;
                    if ($blackGuard && $unitKilled > 1) {
                        $this->queueService->queueResources('training', $dominion, ["military_unit{$unit->slot}" => rfloor(0.75 * $unitKilled)]);
                    }
                }
            }
        }

        $target->stat_wizards_executed += array_sum($unitsKilled);
        $dominion->stat_wizards_lost += array_sum($unitsKilled);

        $unitsKilledStringParts = [];
        foreach ($unitsKilled as $name => $amount) {
            $amountLabel = number_format($amount);
            $unitLabel = Str::plural(Str::singular($name), $amount);
            $unitsKilledStringParts[] = "{$amountLabel} {$unitLabel}";
        }
        $unitsKilledString = generate_sentence_from_array($unitsKilledStringParts);

        return [$unitsKilled, $unitsKilledString];
    }

    /**
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $spellKey
     * @return array
     * @throws Exception
     */
    protected function handleWarResults(Dominion $dominion, Dominion $target, string $spellKey): array
    {
        $damageDealtString = '';
        $warRewardsString = '';

        // Mastery Gains
        $masteryGain = $this->opsCalculator->getMasteryChange($dominion, $target, 'wizard');
        $dominion->wizard_mastery += $masteryGain;

        // Mastery Loss
        $masteryLoss = min($this->opsCalculator->getMasteryChange($dominion, $target, 'wizard', true), $target->wizard_mastery);
        $target->wizard_mastery -= $masteryLoss;

        $warRewardsString = "You gained {$masteryGain} wizard mastery.";
        if ($masteryLoss > 0) {
            $damageDealtString = "{$masteryLoss} wizard mastery";
        }

        return [
            'damageDealt' => $damageDealtString,
            'warRewards' => $warRewardsString,
        ];
    }

    /**
     * @param Dominion $dominion
     * @param Dominion $target
     * @param Spell $spell
     * @param bool $ignoreMeter
     * @param bool $mutualWarDeclared
     * @return string
     */
    protected function handleStatusEffects(Dominion $dominion, Dominion $target, Spell $spell, bool $ignoreMeter, bool $mutualWarDeclared): ?string
    {
        $statusEffect = null;
        if (in_array($spell->key, ['fireball', 'lightning_bolt'])) {
            foreach ($spell->perks as $perk) {
                if (Str::startsWith($perk->key, 'apply_')) {
                    $statusEffectKey = str_replace('apply_', '', $perk->key);
                    $immunity = $target->getSpellPerkValue("immune_{$statusEffectKey}", ['self', 'friendly', 'effect']);
                    if (!$immunity && ($target->{"{$spell->key}_meter"} >= $perk->pivot->value || $ignoreMeter)) {
                        $statusEffectSpell = $this->spellHelper->getSpellByKey($statusEffectKey);
                        $statusEffectActiveSpell = $target->spells->find($statusEffectSpell->id);
                        if ($statusEffectActiveSpell == null) {
                            $statusEffect = $statusEffectSpell->name;
                            $duration = $statusEffectSpell->duration + $target->getTechPerkValue("enemy_{$statusEffectKey}_duration");
                            // Extend duration
                            $duration += clamp(rfloor(($target->round->daysInRound() - 4) / 4), 0, 10);
                            if ($mutualWarDeclared) {
                                $duration += 12;
                            }
                            DominionSpell::create([
                                'dominion_id' => $target->id,
                                'spell_id' => $statusEffectSpell->id,
                                'duration' => $duration,
                                'cast_by_dominion_id' => $dominion->id,
                            ]);
                        }
                    }
                }
            }
        }
        return $statusEffect;
    }
}

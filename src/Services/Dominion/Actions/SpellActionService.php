<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Exception;
use LogicException;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\OpsHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

class SpellActionService
{
    use DominionGuardsTrait;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var OpsHelper */
    protected $opsHelper;

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

    /**
     * SpellActionService constructor.
     */
    public function __construct()
    {
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->opsHelper = app(OpsHelper::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->spellHelper = app(SpellHelper::class);
    }

    public const BLACK_OPS_DAYS_AFTER_ROUND_START = 7;

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
    public function castSpell(Dominion $dominion, string $spellKey, ?Dominion $target = null): array
    {
        $this->guardLockedDominion($dominion);
        if ($target !== null) {
            $this->guardLockedDominion($target);
        }

        $spellInfo = $this->spellHelper->getSpellInfo($spellKey, $dominion->race);

        if (!$spellInfo) {
            throw new LogicException("Cannot cast unknown spell '{$spellKey}'");
        }

        if ($dominion->wizard_strength < 30) {
            throw new GameException("Your wizards to not have enough strength to cast {$spellInfo['name']}.");
        }

        $manaCost = $this->spellCalculator->getManaCost($dominion, $spellKey);

        if ($dominion->resource_mana < $manaCost) {
            throw new GameException("You do not have enough mana to cast {$spellInfo['name']}.");
        }

        if ($this->spellCalculator->isOnCooldown($dominion, $spellKey)) {
            throw new GameException("You can only cast {$spellInfo['name']} every {$spellInfo['cooldown']} hours.");
        }

        if ($this->spellHelper->isOffensiveSpell($spellKey)) {
            if ($target === null) {
                throw new GameException("You must select a target when casting offensive spell {$spellInfo['name']}");
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
        }

        $result = null;

        DB::transaction(function () use ($dominion, $manaCost, $spellKey, &$result, $target) {
            if ($this->spellHelper->isSelfSpell($spellKey, $dominion->race)) {
                $result = $this->castSelfSpell($dominion, $spellKey);

            } elseif ($this->spellHelper->isInfoOpSpell($spellKey)) {
                $result = $this->castInfoOpSpell($dominion, $spellKey, $target);

            } elseif ($this->spellHelper->isHostileSpell($spellKey)) {
                $result = $this->castHostileSpell($dominion, $spellKey, $target);

            } else {
                throw new LogicException("Unknown type for spell {$spellKey}");
            }

            $dominion->resource_mana -= $manaCost;
            $dominion->wizard_strength -= ($result['wizardStrengthCost'] ?? 5);

            if (!$this->spellHelper->isSelfSpell($spellKey, $dominion->race)) {
                if ($result['success']) {
                    $dominion->stat_spell_success += 1;
                } else {
                    $dominion->stat_spell_failure += 1;
                }
            }

            if ($target == null) {
                $dominion->save([
                    'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                    'action' => $spellKey
                ]);
            } else {
                $dominion->save([
                    'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                    'action' => $spellKey,
                    'target_dominion_id' => $target->id
                ]);

                if ($dominion->fresh()->wizard_strength < 25) {
                    throw new GameException("Your wizards have run out of strength");
                }

                $target->save([
                    'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                    'action' => $spellKey,
                    'source_dominion_id' => $dominion->id
                ]);
            }
        });

        if ($target !== null) {
            $this->rangeCalculator->checkGuardApplications($dominion, $target);
        }

        return [
                'message' => $result['message'], /* sprintf(
                    $this->getReturnMessageString($dominion), // todo
                    $spellInfo['name'],
                    number_format($manaCost)
                ),*/
                'data' => [
                    'spell' => $spellKey,
                    'manaCost' => $manaCost,
                ],
                'redirect' =>
                    $this->spellHelper->isInfoOpSpell($spellKey) && $result['success']
                        ? $result['redirect']
                        : null,
            ] + $result;
    }

    /**
     * Casts a self spell for $dominion.
     *
     * @param Dominion $dominion
     * @param string $spellKey
     * @return array
     * @throws GameException
     * @throws LogicException
     */
    protected function castSelfSpell(Dominion $dominion, string $spellKey): array
    {
        $spellInfo = $this->spellHelper->getSpellInfo($spellKey, $dominion->race);

        $where = [
            'dominion_id' => $dominion->id,
            'spell' => $spellKey,
        ];

        $activeSpell = DB::table('active_spells')
            ->where($where)
            ->first();

        if ($activeSpell !== null) {
            if ((int)$activeSpell->duration === $spellInfo['duration']) {
                throw new GameException("Your wizards refused to recast {$spellInfo['name']}, since it is already at maximum duration.");
            }
            DB::table('active_spells')
                ->where($where)
                ->update([
                    'duration' => $spellInfo['duration'],
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('active_spells')
                ->insert([
                    'dominion_id' => $dominion->id,
                    'spell' => $spellKey,
                    'duration' => $spellInfo['duration'],
                    'cast_by_dominion_id' => $dominion->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return [
            'success' => true,
            'message' => sprintf(
                'Your wizards cast the spell successfully, and it will continue to affect your dominion for the next %s hours.',
                $spellInfo['duration']
            )
        ];
    }

    /**
     * Casts an info op spell for $dominion to $target.
     *
     * @param Dominion $dominion
     * @param string $spellKey
     * @param Dominion $target
     * @return array
     * @throws GameException
     * @throws Exception
     */
    protected function castInfoOpSpell(Dominion $dominion, string $spellKey, Dominion $target): array
    {
        $spellInfo = $this->spellHelper->getSpellInfo($spellKey, $dominion->race);

        $selfWpa = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
        $targetWpa = $this->militaryCalculator->getWizardRatio($target, 'defense');

        // You need at least some positive WPA to cast info ops
        if ($selfWpa === 0.0) {
            // Don't reduce mana by throwing an exception here
            throw new GameException("Your wizard force is too weak to cast {$spellInfo['name']}. Please train more wizards.");
        }

        // 100% spell success if target has a WPA of 0
        if ($targetWpa !== 0.0) {
            $successRate = $this->opsHelper->infoOperationSuccessChance($selfWpa, $targetWpa);

            if (!random_chance($successRate)) {
                // Inform target that they repelled a hostile spell
                $this->notificationService
                    ->queueNotification('repelled_hostile_spell', [
                        'sourceDominionId' => $dominion->id,
                        'spellKey' => $spellKey,
                        'unitsKilled' => '',
                    ])
                    ->sendNotifications($target, 'irregular_dominion');

                // Return here, thus completing the spell cast and reducing the caster's mana
                return [
                    'success' => false,
                    'message' => "The enemy wizards have repelled our {$spellInfo['name']} attempt.",
                    'wizardStrengthCost' => 2,
                    'alert-type' => 'warning',
                ];
            }
        }

        $infoOp = new InfoOp([
            'source_realm_id' => $dominion->realm->id,
            'target_realm_id' => $target->realm->id,
            'type' => $spellKey,
            'source_dominion_id' => $dominion->id,
            'target_dominion_id' => $target->id,
        ]);

        switch ($spellKey) {
            case 'clear_sight':
                $infoOp->data = [

                    'ruler_name' => $target->ruler_name,
                    'race_id' => $target->race->id,
                    'land' => $this->landCalculator->getTotalLand($target),
                    'peasants' => $target->peasants,
                    'employment' => $this->populationCalculator->getEmploymentPercentage($target),
                    'networth' => $this->networthCalculator->getDominionNetworth($target),
                    'prestige' => $target->prestige,

                    'resource_platinum' => $target->resource_platinum,
                    'resource_food' => $target->resource_food,
                    'resource_lumber' => $target->resource_lumber,
                    'resource_mana' => $target->resource_mana,
                    'resource_ore' => $target->resource_ore,
                    'resource_gems' => $target->resource_gems,
                    'resource_tech' => $target->resource_tech,
                    'resource_boats' => $target->resource_boats + $this->queueService->getInvasionQueueTotalByResource(
                            $target,
                            'resource_boats'
                        ),

                    'morale' => $target->morale,
                    'military_draftees' => $target->military_draftees,
                    'military_unit1' => $this->militaryCalculator->getTotalUnitsForSlot($target, 1),
                    'military_unit2' => $this->militaryCalculator->getTotalUnitsForSlot($target, 2),
                    'military_unit3' => $this->militaryCalculator->getTotalUnitsForSlot($target, 3),
                    'military_unit4' => $this->militaryCalculator->getTotalUnitsForSlot($target, 4),

                    'recently_invaded_count' => $this->militaryCalculator->getRecentlyInvadedCount($target),

                ];
                break;

            case 'vision':
                $infoOp->data = [
                    'techs' => $target->techs->pluck('name', 'key')->all(),
                    'heroes' => []
                ];
                break;

            case 'revelation':
                $infoOp->data = $this->spellCalculator->getActiveSpells($target);
                break;

            case 'clairvoyance':
                $infoOp->data = [
                    'targetRealmId' => $target->realm->id
                ];
                break;

//            case 'disclosure':
//                $infoOp->data = [];
//                break;

            default:
                throw new LogicException("Unknown info op spell {$spellKey}");
        }

        // Surreal Perception
        if ($this->spellCalculator->isSpellActive($target, 'surreal_perception')) {
            $this->notificationService
                ->queueNotification('received_hostile_spell', [
                    'sourceDominionId' => $dominion->id,
                    'spellKey' => $spellKey,
                ])
                ->sendNotifications($target, 'irregular_dominion');
        }

        $infoOp->save();

        $redirect = route('dominion.op-center.show', $target);
        if ($spellKey === 'clairvoyance') {
            $redirect = route('dominion.op-center.clairvoyance', $target->realm->number);
        }

        return [
            'success' => true,
            'message' => 'Your wizards cast the spell successfully, and a wealth of information appears before you.',
            'wizardStrengthCost' => 2,
            'redirect' => $redirect,
        ];
    }

    /**
     * Casts a hostile spell for $dominion to $target.
     *
     * @param Dominion $dominion
     * @param string $spellKey
     * @param Dominion $target
     * @return array
     * @throws GameException
     * @throws LogicException
     */
    protected function castHostileSpell(Dominion $dominion, string $spellKey, Dominion $target): array
    {
        if ($dominion->round->hasOffensiveActionsDisabled()) {
            throw new GameException('Black ops have been disabled for the remainder of the round.');
        }

        if (now()->diffInDays($dominion->round->start_date) < self::BLACK_OPS_DAYS_AFTER_ROUND_START) {
            throw new GameException('You cannot perform black ops for the first seven days of the round');
        }

        $spellInfo = $this->spellHelper->getSpellInfo($spellKey, $dominion->race);

        if ($this->spellHelper->isWarSpell($spellKey)) {
            $warDeclared = ($dominion->realm->war_realm_id == $target->realm->id || $target->realm->war_realm_id == $dominion->realm->id);
            if (!$warDeclared && !in_array($target->id, $this->militaryCalculator->getRecentlyInvadedBy($dominion, 12))) {
                throw new GameException("You cannot cast {$spellInfo['name']} outside of war.");
            }
        }

        $selfWpa = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
        $targetWpa = $this->militaryCalculator->getWizardRatio($target, 'defense');

        // You need at least some positive WPA to cast info ops
        if ($selfWpa === 0.0) {
            // Don't reduce mana by throwing an exception here
            throw new GameException("Your wizard force is too weak to cast {$spellInfo['name']}. Please train more wizards.");
        }

        // 100% spell success if target has a WPA of 0
        if ($targetWpa !== 0.0) {
            $successRate = $this->opsHelper->blackOperationSuccessChance($selfWpa, $targetWpa);

            if (!random_chance($successRate)) {
                $wizardsKilledBasePercentage = 1;

                // Wizard Guilds
                $wizardGuildCasualtyReduction = 3;
                $wizardGuildWizardCasualtyReductionMax = 30;

                $wizardsKilledBasePercentage = (1 - min(
                    (($dominion->building_wizard_guild / $this->landCalculator->getTotalLand($dominion)) * $wizardGuildCasualtyReduction),
                    ($wizardGuildWizardCasualtyReductionMax / 100)
                ));

                $wizardLossSpaRatio = ($targetWpa / $selfWpa);
                $wizardsKilledPercentage = clamp($wizardsKilledBasePercentage * $wizardLossSpaRatio, 0.5, 1.5);

                $unitsKilled = [];
                $wizardsKilled = (int)floor($dominion->military_wizards * ($wizardsKilledPercentage / 100));

                // Check for immortal wizards
                if ($dominion->race->getPerkValue('immortal_wizards') != 0) {
                    $wizardsKilled = 0;
                }

                if ($wizardsKilled > 0) {
                    $unitsKilled['wizards'] = $wizardsKilled;
                    $dominion->military_wizards -= $wizardsKilled;
                }

                foreach ($dominion->race->units as $unit) {
                    if ($unit->getPerkValue('counts_as_wizard_offense')) {
                        $unitKilledMultiplier = ((float)$unit->getPerkValue('counts_as_wizard_offense') / 2) * ($wizardsKilledPercentage / 100);
                        $unitKilled = (int)floor($dominion->{"military_unit{$unit->slot}"} * $unitKilledMultiplier);
                        if ($unitKilled > 0) {
                            $unitsKilled[strtolower($unit->name)] = $unitKilled;
                            $dominion->{"military_unit{$unit->slot}"} -= $unitKilled;
                        }
                    }
                }

                $target->stat_wizards_executed += array_sum($unitsKilled);

                $unitsKilledStringParts = [];
                foreach ($unitsKilled as $name => $amount) {
                    $amountLabel = number_format($amount);
                    $unitLabel = str_plural(str_singular($name), $amount);
                    $unitsKilledStringParts[] = "{$amountLabel} {$unitLabel}";
                }
                $unitsKilledString = generate_sentence_from_array($unitsKilledStringParts);

                // Inform target that they repelled a hostile spell
                $this->notificationService
                    ->queueNotification('repelled_hostile_spell', [
                        'sourceDominionId' => $dominion->id,
                        'spellKey' => $spellKey,
                        'unitsKilled' => $unitsKilledString,
                    ])
                    ->sendNotifications($target, 'irregular_dominion');

                if ($unitsKilledString) {
                    $message = "The enemy wizards have repelled our {$spellInfo['name']} attempt and managed to kill $unitsKilledString.";
                } else {
                    $message = "The enemy wizards have repelled our {$spellInfo['name']} attempt.";
                }

                // Return here, thus completing the spell cast and reducing the caster's mana
                return [
                    'success' => false,
                    'message' => $message,
                    'wizardStrengthCost' => 5,
                    'alert-type' => 'warning',
                ];
            }
        }

        $spellReflected = false;
        if ($this->spellCalculator->isSpellActive($target, 'energy_mirror') && random_chance(0.2)) {
            $spellReflected = true;
            $reflectedBy = $target;
            $target = $dominion;
            $dominion = $reflectedBy;
            $dominion->stat_spells_reflected += 1;
        }

        if (isset($spellInfo['duration'])) {
            // Cast spell with duration
            if ($this->spellCalculator->isSpellActive($target, $spellKey)) {
                $where = [
                    'dominion_id' => $target->id,
                    'spell' => $spellKey,
                ];

                $activeSpell = DB::table('active_spells')
                    ->where($where)
                    ->first();

                if ($activeSpell === null) {
                    throw new LogicException("Active spell '{$spellKey}' for dominion id {$target->id} not found");
                }

                DB::table('active_spells')
                    ->where($where)
                    ->update([
                        'duration' => $spellInfo['duration'],
                        'cast_by_dominion_id' => $dominion->id,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('active_spells')
                    ->insert([
                        'dominion_id' => $target->id,
                        'spell' => $spellKey,
                        'duration' => $spellInfo['duration'],
                        'cast_by_dominion_id' => $dominion->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            // Update statistics
            if (isset($dominion->{"stat_{$spellInfo['key']}_hours"})) {
                $dominion->{"stat_{$spellInfo['key']}_hours"} += $spellInfo['duration'];
            }

            // Surreal Perception
            $sourceDominionId = null;
            if ($this->spellCalculator->isSpellActive($target, 'surreal_perception')) {
                $sourceDominionId = $dominion->id;
            }

            $this->notificationService
                ->queueNotification('received_hostile_spell', [
                    'sourceDominionId' => $sourceDominionId,
                    'spellKey' => $spellKey,
                ])
                ->sendNotifications($target, 'irregular_dominion');

            if ($spellReflected) {
                // Notification for Energy Mirror deflection
                $this->notificationService
                    ->queueNotification('reflected_hostile_spell', [
                        'sourceDominionId' => $target->id,
                        'spellKey' => $spellKey,
                    ])
                    ->sendNotifications($dominion, 'irregular_dominion');

                return [
                    'success' => true,
                    'message' => sprintf(
                        'Your wizards cast the spell successfully, but it was reflected and it will now affect your dominion for the next %s hours.',
                        $spellInfo['duration']
                    ),
                    'alert-type' => 'danger'
                ];
            } else {
                return [
                    'success' => true,
                    'message' => sprintf(
                        'Your wizards cast the spell successfully, and it will continue to affect your target for the next %s hours.',
                        $spellInfo['duration']
                    )
                ];
            }
        } else {
            // Cast spell instantly
            $damageDealt = [];
            $totalDamage = 0;
            $baseDamage = (isset($spellInfo['percentage']) ? $spellInfo['percentage'] : 1) / 100;

            if (isset($spellInfo['decreases'])) {
                foreach ($spellInfo['decreases'] as $attr) {
                    $damage = $target->{$attr} * $baseDamage;

                    // Fireball damage reduction from Forest Havens
                    if ($attr == 'peasants') {
                        $forestHavenFireballReduction = 10;
                        $forestHavenFireballReductionMax = 80;
                        $damageMultiplier = (1 - min(
                            (($target->building_forest_haven / $this->landCalculator->getTotalLand($target)) * $forestHavenFireballReduction),
                            ($forestHavenFireballReductionMax / 100)
                        ));
                        $damage *= $damageMultiplier;
                    }

                    // Disband Spies damage reduction from Forest Havens
                    if ($attr == 'military_spies') {
                        $forestHavenSpyCasualtyReduction = 3;
                        $forestHavenSpyCasualtyReductionMax = 30;
                        $damageMultiplier = (1 - min(
                            (($target->building_forest_haven / $this->landCalculator->getTotalLand($target)) * $forestHavenSpyCasualtyReduction),
                            ($forestHavenSpyCasualtyReductionMax / 100)
                        ));
                        $damage *= $damageMultiplier;
                    }

                    // Damage reduction from Masonries
                    if (strpos($attr, 'improvement_') === 0) {
                        $masonryLightningBoltReduction = 0.75;
                        $masonryLightningBoltReductionMax = 25;
                        $damageMultiplier = (1 - min(
                            (($target->building_masonry / $this->landCalculator->getTotalLand($target)) * $masonryLightningBoltReduction),
                            ($masonryLightningBoltReductionMax / 100)
                        ));
                        $damage *= $damageMultiplier;
                    }

                    // Damage reduction from Towers
                    $damage *= (1 - $this->improvementCalculator->getImprovementMultiplierBonus($target, 'towers'));

                    $totalDamage += round($damage);
                    $target->{$attr} -= round($damage);
                    $damageDealt[] = sprintf('%s %s', number_format($damage), dominion_attr_display($attr, $damage));

                    // Update statistics
                    if (isset($dominion->{"stat_{$spellInfo['key']}_damage"})) {
                        // Only count peasants killed by fireball
                        if (!($spellInfo['key'] == 'fireball' && $attr == 'resource_food')) {
                            $dominion->{"stat_{$spellInfo['key']}_damage"} += round($damage);
                        }
                    }
                }

                // Combine lightning bolt damage into single string
                if ($spellInfo['key'] === 'lightning_bolt') {
                    // Combine lightning bold damage into single string
                    $damageDealt = [sprintf('%s %s', number_format($totalDamage), dominion_attr_display('improvement', $totalDamage))];
                }
            }
            if (isset($spellInfo['increases'])) {
                foreach ($spellInfo['increases'] as $attr) {
                    if ($attr == 'military_draftees') {
                        $target->{$attr} += $totalDamage;
                    } else {
                        $damage = $target->{$attr} * $baseDamage;

                        // Damage reduction from Towers
                        $damage *= (1 - $this->improvementCalculator->getImprovementMultiplierBonus($target, 'towers'));

                        $target->{$attr} += round($damage);
                    }
                }
            }

            // Prestige Gains
            $prestigeGainString = '';
            if ($this->spellHelper->isWarSpell($spellKey) && ($dominion->realm->war_realm_id == $target->realm->id && $target->realm->war_realm_id == $dominion->realm->id) && $totalDamage > 0) {
                $dominion->prestige += 2;
                $dominion->stat_wizard_prestige += 2;
                $prestigeGainString = 'You were awarded 2 prestige due to mutual war.';
            }

            // Surreal Perception
            $sourceDominionId = null;
            if ($this->spellCalculator->isSpellActive($target, 'surreal_perception')) {
                $sourceDominionId = $dominion->id;
            }

            $damageString = generate_sentence_from_array($damageDealt);

            $this->notificationService
                ->queueNotification('received_hostile_spell', [
                    'sourceDominionId' => $sourceDominionId,
                    'spellKey' => $spellKey,
                    'damageString' => $damageString,
                ])
                ->sendNotifications($target, 'irregular_dominion');

            if ($spellReflected) {
                // Notification for Energy Mirror defelection
                $this->notificationService
                    ->queueNotification('reflected_hostile_spell', [
                        'sourceDominionId' => $target->id,
                        'spellKey' => $spellKey,
                    ])
                    ->sendNotifications($dominion, 'irregular_dominion');

                return [
                    'success' => true,
                    'message' => sprintf(
                        'Your wizards cast the spell successfully, but it was reflected and your dominion lost %s.',
                        $damageString
                    ),
                    'wizardStrengthCost' => 5,
                    'alert-type' => 'danger'
                ];
            } else {
                return [
                    'success' => true,
                    'message' => sprintf(
                        'Your wizards cast the spell successfully, your target lost %s. %s',
                        $damageString,
                        $prestigeGainString
                    ),
                    'wizardStrengthCost' => 5,
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
}

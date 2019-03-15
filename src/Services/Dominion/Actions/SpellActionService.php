<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;
use Throwable;

class SpellActionService
{
    use DominionGuardsTrait;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

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
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->spellHelper = app(SpellHelper::class);
    }

    /**
     * Casts a magic spell for a dominion, optionally aimed at another dominion.
     *
     * @param Dominion $dominion
     * @param string $spellKey
     * @param null|Dominion $target
     * @return array
     * @throws Throwable
     */
    public function castSpell(Dominion $dominion, string $spellKey, ?Dominion $target = null): array
    {
        $this->guardLockedDominion($dominion);

        $spellInfo = $this->spellHelper->getSpellInfo($spellKey, $dominion->race);

        if (!$spellInfo) {
            throw new RuntimeException("Cannot cast unknown spell '{$spellKey}'");
        }

        if ($dominion->wizard_strength < 30) {
            throw new RuntimeException("Your wizards to not have enough strength to cast {$spellInfo['name']}.");
        }

        $manaCost = ($spellInfo['mana_cost'] * $this->landCalculator->getTotalLand($dominion));

        if ($dominion->resource_mana < $manaCost) {
            throw new RuntimeException("You do not have enough mana to cast {$spellInfo['name']}.");
        }

        if ($this->spellHelper->isOffensiveSpell($spellKey)) {
            if ($target === null) {
                throw new RuntimeException("You must select a target when casting offensive spell {$spellInfo['name']}");
            }

            if ($this->protectionService->isUnderProtection($dominion)) {
                throw new RuntimeException('You cannot cast offensive spells while under protection');
            }

            if ($this->protectionService->isUnderProtection($target)) {
                throw new RuntimeException('You cannot cast offensive spells to targets which are under protection');
            }

            if (!$this->rangeCalculator->isInRange($dominion, $target)) {
                throw new RuntimeException('You cannot cast offensive spells to targets outside of your range');
            }

            if ($dominion->round->id !== $target->round->id) {
                throw new RuntimeException('Nice try, but you cannot cast spells cross-round');
            }

            if ($dominion->realm->id === $target->realm->id) {
                throw new RuntimeException('Nice try, but you cannot cast spells on your realmies');
            }
        }

        $result = null;

        DB::transaction(function () use ($dominion, $manaCost, $spellKey, &$result, $target) {
            if ($this->spellHelper->isSelfSpell($spellKey, $dominion->race)) {
                $result = $this->castSelfSpell($dominion, $spellKey);

            } elseif ($this->spellHelper->isInfoOpSpell($spellKey)) {
                $result = $this->castInfoOpSpell($dominion, $spellKey, $target);

            } elseif ($this->spellHelper->isBlackOpSpell($spellKey)) {
                throw new LogicException('Not yet implemented');

            } elseif ($this->spellHelper->isWarSpell($spellKey)) {
                throw new LogicException('Not yet implemented');

            } else {
                throw new LogicException("Unknown type for spell {$spellKey}");
            }

            $dominion->resource_mana -= $manaCost;
            $dominion->wizard_strength -= ($result['wizardStrengthCost'] ?? 5);
            $dominion->save(['event' => HistoryService::EVENT_ACTION_CAST_SPELL]);
        });

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
                        ? route('dominion.op-center.show', $target->id)
                        : null,
            ] + $result;
    }

    /**
     * Casts a self spell for $dominion.
     *
     * @param Dominion $dominion
     * @param string $spellKey
     * @return array
     */
    protected function castSelfSpell(Dominion $dominion, string $spellKey): array
    {
        $spellInfo = $this->spellHelper->getSpellInfo($spellKey, $dominion->race);

        if ($this->spellCalculator->isSpellActive($dominion, $spellKey)) {

            $where = [
                'dominion_id' => $dominion->id,
                'spell' => $spellKey,
            ];

            $activeSpell = DB::table('active_spells')
                ->where($where)
                ->first();

            if ($activeSpell === null) {
                throw new LogicException("Active spell '{$spellKey}' for dominion id {$dominion->id} not found");
            }

            if ((int)$activeSpell->duration === $spellInfo['duration']) {
                throw new RuntimeException("Your wizards refused to recast {$spellInfo['name']}, since it is already at maximum duration.");
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
            'message' => 'Your wizards cast the spell successfully, and it will continue to affect your dominion for the next 12 hours.',
        ];
    }

    /**
     * Casts an info op spell for $dominion to $target.
     *
     * @param Dominion $dominion
     * @param string $spellKey
     * @param Dominion $target
     * @return array
     */
    protected function castInfoOpSpell(Dominion $dominion, string $spellKey, Dominion $target): array
    {
        $spellInfo = $this->spellHelper->getSpellInfo($spellKey, $dominion->race);

        $selfWpa = $this->militaryCalculator->getWizardRatio($dominion);
        $targetWpa = $this->militaryCalculator->getWizardRatio($target);

        // You need at least some positive WPA to cast info ops
        if ($selfWpa === 0.0) {
            // Don't reduce mana by throwing an exception here
            throw new RuntimeException("Your wizard force is too weak to cast {$spellInfo['name']}. Please train more wizards.");
        }

        // 100% spell success if target has a WPA of 0
        if ($targetWpa !== 0.0) {
            $ratio = ($selfWpa / $targetWpa);

            // Exact formula from Dom is unknown. Thanks to mriswith on Discord for coming up with this formula <3
            $successRate = (
                (0.0172 * ($ratio ** 3))
                - (0.1809 * ($ratio ** 2))
                + (0.6767 * $ratio)
                - 0.0134
            );

            if (!random_chance($successRate)) {
                // Return here, thus completing the spell cast and reducing the caster's mana
                return [
                    'success' => false,
                    'message' => "The enemy wizards have repelled our {$spellInfo['name']} attempt.",
                    'wizardStrengthCost' => 2,
                    'alert-type' => 'warning',
                ];
            }
        }

        // todo: take Energy Mirror into account with 20% spell reflect (either show your info or give the infoop to the target)

        $infoOp = InfoOp::firstOrNew([
            'source_realm_id' => $dominion->realm->id,
            'target_dominion_id' => $target->id,
            'type' => $spellKey,
        ], [
            'source_dominion_id' => $dominion->id,
        ]);

        if ($infoOp->exists) {
            // Overwrite casted_by_dominion_id for the newer data
            $infoOp->source_dominion_id = $dominion->id;
        }

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
                    'resource_boats' => $target->resource_boats,

                    'morale' => $target->morale,
                    'military_draftees' => $target->military_draftees,
                    'military_unit1' => $this->militaryCalculator->getTotalUnitsForSlot($target, 1),
                    'military_unit2' => $this->militaryCalculator->getTotalUnitsForSlot($target, 2),
                    'military_unit3' => $this->militaryCalculator->getTotalUnitsForSlot($target, 3),
                    'military_unit4' => $this->militaryCalculator->getTotalUnitsForSlot($target, 4),

                    'recently_invaded_count' => $this->militaryCalculator->getRecentlyInvadedCount($target),

                ];
                break;

//            case 'vision':
//                $infoOp->data = [];
//                break;

            case 'revelation':
                $infoOp->data = $this->spellCalculator->getActiveSpells($target);
                break;

//            case 'clairvoyance':
//                $infoOp->data = [
            // tc
//                ];
//                break;

//            case 'disclosure':
//                $infoOp->data = [];
//                break;

            default:
                throw new LogicException("Unknown info op spell {$spellKey}");
        }

        // Always force update updated_at on infoops to know when the last infoop was cast
        $infoOp->updated_at = now(); // todo: fixable with ->save(['touch'])?
        $infoOp->save();

        return [
            'success' => true,
            'message' => 'Your wizards cast the spell successfully, and a wealth of information appears before you.',
            'wizardStrengthCost' => 2,
            'redirect' => route('dominion.op-center.show', $target),
        ];
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

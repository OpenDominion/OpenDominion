<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\Queue\TrainingQueueService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;
use Throwable;

class SpellActionService
{
    use DominionGuardsTrait;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var SpellHelper */
    protected $spellHelper;

    /** @var TrainingQueueService */
    protected $trainingQueueService;

    /**
     * SpellActionService constructor.
     *
     * @param LandCalculator $landCalculator
     * @param NetworthCalculator $networthCalculator
     * @param PopulationCalculator $populationCalculator
     * @param SpellCalculator $spellCalculator
     * @param SpellHelper $spellHelper
     * @param TrainingQueueService $trainingQueueService
     */
    public function __construct(
        LandCalculator $landCalculator,
        NetworthCalculator $networthCalculator,
        PopulationCalculator $populationCalculator,
        SpellCalculator $spellCalculator,
        SpellHelper $spellHelper,
        TrainingQueueService $trainingQueueService
    ) {
        $this->landCalculator = $landCalculator;
        $this->networthCalculator = $networthCalculator;
        $this->populationCalculator = $populationCalculator;
        $this->spellCalculator = $spellCalculator;
        $this->spellHelper = $spellHelper;
        $this->trainingQueueService = $trainingQueueService;
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

        $spellInfo = $this->spellHelper->getSpellInfo($spellKey);

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

            if ($dominion->round->id !== $target->round->id) {
                throw new RuntimeException('Nice try, but you cannot cast spells cross-round');
            }

            if ($dominion->realm->id === $target->realm->id) {
                throw new RuntimeException('Nice try, but you cannot cast spells on your realmies');
            }
        }

        $result = null;

        DB::transaction(function () use ($dominion, $manaCost, $spellKey, &$result, $target) {

            if ($this->spellHelper->isSelfSpell($spellKey)) {
                $result = $this->castSelfSpell($dominion, $spellKey);

            } elseif ($this->spellHelper->isInfoOpSpell($spellKey)) {
                $result = $this->castInfoOpSpell($dominion, $spellKey, $target);

            } elseif ($this->spellHelper->isBlackOpSpell($spellKey)) {
                throw new LogicException('Not yet implemented');

            } elseif ($this->spellHelper->isWarSpell($spellKey)) {
                throw new LogicException('Not yet implemented');

            } else {
                throw new LogicException("Unknown spell type for spell {$spellKey}");
            }

            $dominion->resource_mana -= $manaCost;
            $dominion->wizard_strength -= 5;
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
                'redirect' => (
                    $this->spellHelper->isInfoOpSpell($spellKey)
                        ? route('dominion.op-center.show', $target->id)
                        : null
                ),
            ] + $result;
    }

    protected function castSelfSpell(Dominion $dominion, string $spellKey): array
    {
        $spellInfo = $this->spellHelper->getSpellInfo($spellKey);

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
            'message' => 'Your wizards cast the spell successfully, and it will continue to affect your dominion for the next 12 hours.',
        ];
    }

    protected function castInfoOpSpell(Dominion $dominion, string $spellKey, Dominion $target): array
    {
        $infoOp = InfoOp::firstOrNew([
            'source_realm_id' => $dominion->realm->id,
            'target_dominion_id' => $target->id,
            'type' => $spellKey,
        ], [
            'source_dominion_id' => $dominion->id,
        ]);

        if ($infoOp->exists) {
            $infoOp->source_dominion_id = $dominion->id;
        }

        switch ($spellKey) {
            case 'clear_sight':
                $infoOp->data = [

                    'ruler_name' => $target->user->display_name, // todo: $target->ruler_name
                    'race' => $target->race->name,
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
                    'military_unit1' => $target->military_unit1,
                    'military_unit2' => $target->military_unit2,
                    'military_unit3' => $target->military_unit3,
                    'military_unit4' => $target->military_unit4,

                ];
                break;

//            case 'vision':
//                $infoOp->data = [];
//                break;

            case 'revelation':
                $infoOp->data = $this->spellCalculator->getActiveSpells($target);
                break;

            case 'clairvoyance':
                $infoOp->data = [
                    // tc
                ];
                break;

//            case 'disclosure':
//                $infoOp->data = [];
//                break;

            default:
                throw new LogicException("Unknown info op spell {$spellKey}");
        }

        $infoOp->updated_at = now(); // Always force update updated_at on infoops to know which the last infoop was cast
        $infoOp->save();

        return [
            'message' => 'Your wizards cast the spell successfully, and a wealth of information appears before you.',
            'redirect' => route('dominion.op-center.show', $target),
        ];
    }

    protected function castClearSight(Dominion $dominion, Dominion $target)
    {


        dd('cast clear sight');

        // status screen of $target

        /*

        Model: Realm
        - infoOps(): InfoOp[]
        - infoOpTargetDominions(): Dominion through InfoOp->targetDominion()
        - infoOpFavoriteDominions(): Dominion through info_op_favorite_dominions.target_dominion_id?

        Model: InfoOp
        - realm(): Realm
        - castByDominion(): Dominion
        - targetDominion(): Dominions
        - isStale(): bool - updated_at > last hour

        */

    }

    // todo: vision

    protected function castRevelation(Dominion $dominion, Dominion $target)
    {
        // spells affecting $target
    }

    protected function castClairvoyance(Dominion $dominion, Dominion $target)
    {
        // $target's TC
    }

    // todo: disclosure

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

            if ($this->trainingQueueService->getQueueTotalByUnitType($dominion, 'military_wizards') > 0) {
                $thoughts[] = 'carefully observes the trainee wizards';
            } else {
                $thoughts[] = 'mumbles something about the lack of student wizards to teach';
            }

            if ($this->trainingQueueService->getQueueTotalByUnitType($dominion, 'military_archmages') > 0) {
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

            if ($this->trainingQueueService->getQueueTotalByUnitType($dominion, 'wizards') > 0) {
                $thoughts[] = 'mumbles something about being delighted by the new wizard trainees so he won\'t be lonely anymore';
            } else {
                $thoughts[] = 'mumbles something about not having enough peers to properly conduct his studies';
                $thoughts[] = 'mumbles something about feeling a bit lonely';
            }

            if ($this->trainingQueueService->getQueueTotalByUnitType($dominion, 'archmages') > 0) {
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

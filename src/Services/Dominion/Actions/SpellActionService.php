<?php

namespace OpenDominion\Services\Dominion\Actions;

use Carbon\Carbon;
use DB;
use Exception;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Queue\TrainingQueueService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class SpellActionService
{
    use DominionGuardsTrait;

    /** @var LandCalculator */
    protected $landCalculator;

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
     * @param SpellCalculator $spellCalculator
     * @param SpellHelper $spellHelper
     * @param TrainingQueueService $trainingQueueService
     */
    public function __construct(
        LandCalculator $landCalculator,
        SpellCalculator $spellCalculator,
        SpellHelper $spellHelper,
        TrainingQueueService $trainingQueueService
    ) {
        $this->landCalculator = $landCalculator;
        $this->spellCalculator = $spellCalculator;
        $this->spellHelper = $spellHelper;
        $this->trainingQueueService = $trainingQueueService;
    }

    public function castSelfSpell(Dominion $dominion, string $spell): array
    {
        $this->guardLockedDominion($dominion);

        $spellInfo = $this->spellHelper->getSpellInfo($spell);

        if (!$spellInfo) {
            throw new RuntimeException("Unable to cast spell {$spell}");
        }

        if ($dominion->wizard_strength < 30) {
            throw new RuntimeException("Not enough wizard strength to cast {$spellInfo['name']}.");
        }

        if (($dominion->military_wizards + $dominion->military_archmages) === 0) {
            throw new RuntimeException("You need at least 1 wizard or archmage to cast {$spellInfo['name']}.");
        }

        $manaCost = ($spellInfo['mana_cost'] * $this->landCalculator->getTotalLand($dominion));

        if ($dominion->resource_mana < $manaCost) {
            throw new RuntimeException("Not enough mana to cast {$spellInfo['name']}.");
        }

        try {
            DB::beginTransaction();

            if ($this->spellCalculator->isSpellActive($dominion, $spell)) {

                $where = [
                    'dominion_id' => $dominion->id,
                    'spell' => $spell,
                ];

                $activeSpell = DB::table('active_spells')
                    ->where($where)
                    ->first();

                /** @noinspection NullPointerExceptionInspection */
                if ((int)$activeSpell->duration === $spellInfo['duration']) {
                    throw new RuntimeException("Spell {$spellInfo['name']} is already at maximum duration.");
                }

                DB::table('active_spells')
                    ->where($where)
                    ->update([
                        'duration' => $spellInfo['duration'],
                        'updated_at' => Carbon::now(),
                    ]);

            } else {
                DB::table('active_spells')
                    ->insert([
                        'dominion_id' => $dominion->id,
                        'spell' => $spell,
                        'duration' => $spellInfo['duration'],
                        'cast_by_dominion_id' => $dominion->id, // todo
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
            }

            $dominion->resource_mana -= $manaCost;
            $dominion->wizard_strength -= 5;
            $dominion->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return [
            'message' => sprintf(
                $this->getReturnMessageString($dominion),
                $spellInfo['name'],
                number_format($manaCost)
            ),
            'data' => [
                'spell' => $spell,
                'manaCost' => $manaCost,
            ]
        ];
    }

    /**
     * Returns the successful return message.
     *
     * Little easter egg because I was bored.
     *
     * @param Dominion $dominion
     * @return string
     */
    protected function getReturnMessageString(Dominion $dominion): string
    {
        if ($dominion->military_wizards === 0) {
            if ($dominion->military_archmages > 1) {
                return 'Your archmages successfully cast %s at a cost of %s mana.';
            }

            $thoughts = [
                'mumbles something about being the most powerful sorcerer in the dominion is a lonely job, "but somebody\'s got to do it"',
                'mumbles something about the food being quite delicious',
                'winks at you',
            ];

            if ($this->trainingQueueService->getQueueTotalByUnitType($dominion, 'military_wizards') > 0) {
                $thoughts[] = 'carefully observes the trainee wizards';
            } else {
                $thoughts[] = 'mumbles something about the lack of student wizards to teach';
            }

            if ($this->trainingQueueService->getQueueTotalByUnitType($dominion, 'military_archmages') > 0) {
                $thoughts[] = 'mumbles something about being a bit sad because she probably won\'t be the single most powerful sorcerer in the dominion anymore';
                $thoughts[] = 'mumbles something about looking forward to discuss the secrets of arcane knowledge with her future peers';
            } else {
                $thoughts[] = 'mumbles something about not having enough peers to properly conduct her studies';
            }

            return ('Your single archmage successfully casts %s at a cost of %s mana. In addition, she ' . array_rand($thoughts) . '.');
        }

        if ($dominion->military_archmages === 0) {
            if ($dominion->military_wizards > 1) {
                return 'Your wizards successfully cast %s at a cost of %s mana.';
            }

            $thoughts = [
                'he mumbles something about the food being very delicious',
            ];

            if ($this->trainingQueueService->getQueueTotalByUnitType($dominion, 'military_wizards') > 0) {
                $thoughts[] = 'mumbles something about being delighted by the new wizard trainees so he won\'t be lonely anymore';
            } else {
                $thoughts[] = 'mumbles something about not having enough peers to properly conduct his studies';
                $thoughts[] = 'mumbles something about being a bit lonely';
            }

            if ($this->trainingQueueService->getQueueTotalByUnitType($dominion, 'military_archmages') > 0) {
                $thoughts[] = 'mumbles something about looking forward to his future teacher';
            } else {
                $thoughts[] = 'mumbles something about not having an archmage master to study under';
            }

            if ($dominion->military_spies === 1) {
                $thoughts[] = 'mumbles something about fancying that spy lady';
            } elseif ($dominion->military_spies > 1) {
                $thoughts[] = 'mumbles something about thinking your spies are complotting against him';
            }

            return ('Your single wizard successfully casts %s at a cost of %s mana. In addition, he ' . array_rand($thoughts) . '.');
        }

        if (($dominion->military_wizards === 1) && ($dominion->military_archmages === 1)) {
            $strings = [
                'Your single wizard and your single archmage successfully cast %s together in harmony at a cost of %s mana. It was glorious to behold.',
                'Your wizard watches in awe while his teacher archmage blissfully casts %s at a cost of %s mana.',
                'Your archmage facepalms as he observes his wizard student almost failing to cast %s at a cost of %s mana.',
                'Your single wizard successfully casts %s at a cost of %s mana, while his teacher archmage watches him with pride.',
            ];

            return array_rand($strings);
        }

        if (($dominion->military_wizards === 1) && ($dominion->military_archmages > 1)) {
            $strings = [
                'Your wizards successfully cast %s at a cost of %s mana.',
                'Your wizard was sleeping, so your archmages successfully cast %s at a cost of %s mana.',
                'Your wizard watches carefully while your archmages successfully cast %s at a cost of %s mana.',
            ];

            return array_rand($strings);
        }

        if (($dominion->military_wizards > 1) && ($dominion->military_archmages === 1)) {
            $strings = [
                'Your wizards successfully cast %s at a cost of %s mana.',
                'Your archmage found herself lost in her study books, so your wizards successfully cast %s at a cost of %s mana.',
            ];

            return array_rand($strings);
        }

        return 'Your wizards successfully cast %s at a cost of %s mana.';
    }
}

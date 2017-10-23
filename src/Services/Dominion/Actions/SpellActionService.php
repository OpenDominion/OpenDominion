<?php

namespace OpenDominion\Services\Dominion\Actions;

use Carbon\Carbon;
use DB;
use Exception;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
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

    /**
     * SpellActionService constructor.
     *
     * @param LandCalculator $landCalculator
     * @param SpellCalculator $spellCalculator
     * @param SpellHelper $spellHelper
     */
    public function __construct(LandCalculator $landCalculator, SpellCalculator $spellCalculator, SpellHelper $spellHelper)
    {
        $this->landCalculator = $landCalculator;
        $this->spellCalculator = $spellCalculator;
        $this->spellHelper = $spellHelper;
    }

    public function castSelfSpell(Dominion $dominion, string $spell): array
    {
        $this->guardLockedDominion($dominion);

        if ($dominion->wizard_strength < 30) {
            throw new RuntimeException("Not enough wizard strength to cast {$spell}");
        }

        $spellInfo = $this->spellHelper->getSpellInfo($spell);

        if (!$spellInfo) {
            throw new RuntimeException("Unable to cast spell {$spell}");
        }

        $manaCost = ($spellInfo['mana_cost'] * $this->landCalculator->getTotalLand($dominion));

        if ($dominion->resource_mana < $manaCost) {
            throw new RuntimeException("Not enough mana to cast {$spellInfo['name']}");
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
                'Your wizards successfully cast %s at a cost of %s mana.',
                $spellInfo['name'],
                number_format($manaCost)
            ),
            'data' => [
                'spell' => $spell,
                'manaCost' => $manaCost,
            ]
        ];
    }
}

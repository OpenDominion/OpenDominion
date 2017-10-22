<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;

class SpellCalculator
{
    /** @var Collection */
    protected $activeSpells;

    public function getActiveSpells(Dominion $dominion): Collection
    {
        if ($this->activeSpells === null) {
            $this->activeSpells = \DB::table('active_spells')
                ->leftJoin('dominions', 'dominions.id', '=', 'cast_by_dominion_id')
                ->leftJoin('realms', 'realms.id', '=', 'dominions.id')
                ->where('dominion_id', $dominion->id)
                ->orderBy('duration', 'desc')
                ->orderBy('created_at')
                ->get([
                    'active_spells.*',
                    'dominions.name AS cast_by_dominion_name',
                    'realms.number AS cast_by_dominion_realm_number',
                ]);
        }

        return $this->activeSpells;
    }

    public function isSpellActive(Dominion $dominion, string $spell): bool
    {
        return $this->getActiveSpells($dominion)->contains(function ($value) use ($spell) {
            return ($value->spell === $spell);
        });
    }

    public function getSpellDuration(Dominion $dominion, string $spell): ?int
    {
        if (!$this->isSpellActive($dominion, $spell)) {
            return null;
        }

        $spell = $this->getActiveSpells($dominion)->filter(function ($value) use ($spell) {
            return ($value->spell === $spell);
        })->first();

        return $spell->duration;
    }
}

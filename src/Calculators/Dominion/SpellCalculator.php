<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use Illuminate\Support\Collection;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;

// todo: rename params $spell to $spellKey for clarity. Also use $spellInfo for just info. Spell instances should be $spell
class SpellCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var SpellHelper */
    protected $spellHelper;

    /** @var array */
    protected $activeSpells = [];

    /**
     * SpellCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param SpellHelper $spellHelper
     */
    public function __construct(LandCalculator $landCalculator, SpellHelper $spellHelper)
    {
        $this->landCalculator = $landCalculator;
        $this->spellHelper = $spellHelper;
    }

    /**
     * Returns the mana cost of a particular spell for $dominion.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return int
     */
    public function getManaCost(Dominion $dominion, string $spell): int
    {
        $spellInfo = $this->spellHelper->getSpellInfo($spell, $dominion->race);
        return round($spellInfo['mana_cost'] * $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns whether $dominion can currently cast spell $type.
     *
     * Spells require mana and enough wizard strength to be cast.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return bool
     */
    public function canCast(Dominion $dominion, string $spell): bool
    {
        return (
            ($dominion->resource_mana >= $this->getManaCost($dominion, $spell)) &&
            ($dominion->wizard_strength >= 30)
        );
    }

    /**
     * Returns a list of spells currently affecting $dominion.
     *
     * @param Dominion $dominion
     * @param bool $forceRefresh
     * @return Collection
     */
    public function getActiveSpells(Dominion $dominion, bool $forceRefresh = false): Collection
    {
        $cacheKey = $dominion->id;

        if (!$forceRefresh && array_has($this->activeSpells, $cacheKey)) {
            return collect(array_get($this->activeSpells, $cacheKey));
        }

        $data = DB::table('active_spells')
            ->join('dominions', 'dominions.id', '=', 'cast_by_dominion_id')
            ->join('realms', 'realms.id', '=', 'dominions.realm_id')
            ->where('dominion_id', $dominion->id)
            ->orderBy('duration', 'desc')
            ->orderBy('created_at')
            ->get([
                'active_spells.*',
                'dominions.name AS cast_by_dominion_name',
                'realms.number AS cast_by_dominion_realm_number',
            ]);

        array_set($this->activeSpells, $cacheKey, $data->toArray());

        return $data;
    }

    /**
     * Returns whether a particular spell is affecting $dominion right now.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return bool
     */
    public function isSpellActive(Dominion $dominion, string $spell): bool
    {
        return $this->getActiveSpells($dominion)->contains(function ($value) use ($spell) {
            return ($value->spell === $spell);
        });
    }

    /**
     * Returns the remaining duration (in ticks) of a spell affecting $dominion.
     *
     * @todo Rename to getSpellRemainingDuration for clarity
     * @param Dominion $dominion
     * @param string $spell
     * @return int|null
     */
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

    /**
     * Returns the multiplier bonus when one or more spells are active for a
     * Dominion.
     *
     * Returns the first active spell it finds. Multiple active spells do not
     * stack.
     *
     * @param Dominion $dominion
     * @param string|array $spell
     * @param float|null $bonusPercentage
     * @return float
     */
    public function getActiveSpellMultiplierBonus(Dominion $dominion, $spell, float $bonusPercentage = null): float
    {
        if (!is_array($spell)) {
            $spell = [$spell => $bonusPercentage];
        }

        // todo: check this foreach
        foreach ($spell as $spellName => $bonusPercentage) {
            if ($this->isSpellActive($dominion, $spellName)) {
                return ($bonusPercentage / 100);
            }
        }

        return 0;
    }
}

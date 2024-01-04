<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Spell;

class SpellCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var SpellHelper */
    protected $spellHelper;

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
    public function getManaCost(Dominion $dominion, Spell $spell): int
    {
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        $spellCostMultiplier = 1;

        // Techs
        $spellCostMultiplier += $dominion->getTechPerkMultiplier('spell_cost');
        if ($this->spellHelper->isSelfSpell($spell)) {
            $spellCostMultiplier += $dominion->getTechPerkMultiplier('self_spell_cost');
        }
        if ($this->spellHelper->isRacialSelfSpell($spell)) {
            $spellCostMultiplier += $dominion->getTechPerkMultiplier('racial_spell_cost');
        }
        if ($spell->key == 'fools_gold' && $dominion->getTechPerkMultiplier('fools_gold_cost') !== 0) {
            $spellCostMultiplier += $dominion->getTechPerkMultiplier('fools_gold_cost');
        }

        // Wonders
        $spellCostMultiplier += $dominion->getWonderPerkMultiplier('spell_cost');

        $manaCost = round($spell->cost_mana * $totalLand * $spellCostMultiplier);

        // Amplify Magic
        if ($this->isSpellActive($dominion, 'amplify_magic')) {
            if ($this->spellHelper->isSelfSpell($spell) && !$spell->cooldown) {
                $manaCost = round($manaCost * (1 + $dominion->getSpellPerkValue('self_spell_cost') / 100));
            }
        }

        return $manaCost;
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
    public function canCast(Dominion $dominion, Spell $spell): bool
    {
        $wizardStrengthCost = $spell->cost_strength;

        return (
            ($dominion->resource_mana >= $this->getManaCost($dominion, $spell)) &&
            ($dominion->wizard_strength >= 30)
        );
    }

    /**
     * Returns whether spell $type for $dominion is on cooldown.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return bool
     */
    public function isOnCooldown(Dominion $dominion, Spell $spell): bool
    {
        if ($this->getSpellCooldown($dominion, $spell) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Returns the number of hours before spell $type for $dominion can be cast.
     *
     * @param Dominion $dominion
     * @param string $spell
     * @return bool
     */
    public function getSpellCooldown(Dominion $dominion, Spell $spell): int
    {

        if ($spell->cooldown > 0) {
            $spellLastCast = DB::table('dominion_history')
                ->where('dominion_id', $dominion->id)
                ->where('event', 'cast spell')
                ->where('delta', 'like', "%{$spell->key}%")
                ->orderby('created_at', 'desc')
                ->take(1)
                ->first();
            if ($spellLastCast) {
                $hoursSinceCast = now()->startOfHour()->diffInHours(Carbon::parse($spellLastCast->created_at)->startOfHour());
                if ($hoursSinceCast < $spell->cooldown) {
                    return $spell->cooldown - $hoursSinceCast;
                }
            }
        }

        return 0;
    }

    /**
     * Returns all active spells for a dominion.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function getActiveSpells(Dominion $dominion): Collection
    {
        return DominionSpell::with(['castByDominion', 'spell'])->where('dominion_id', $dominion->id)->where('duration', '>', 0)->get();
    }

    /**
     * Returns whether a particular spell is affecting $dominion right now.
     *
     * @param Dominion $dominion
     * @param string $spellKey
     * @return bool
     */
    public function isSpellActive(Dominion $dominion, string $spellKey): bool
    {
        return $dominion->spells->pluck('key')->contains($spellKey);
    }

    /**
     * Returns the remaining duration (in ticks) of a spell affecting $dominion.
     *
     * @param Dominion $dominion
     * @param Spell $spell
     * @return int|null
     */
    public function getSpellDurationRemaining(Dominion $dominion, Spell $spell): ?int
    {
        if (!$dominion->spells->contains($spell)) {
            return null;
        }

        return $dominion->spells->find($spell->id)->pivot->duration;
    }

    /**
     * Returns the duration (in ticks) of a spell for a $dominion.
     *
     * @param Dominion $dominion
     * @param Spell $spell
     * @return int|null
     */
    public function getSpellDuration(Dominion $dominion, Spell $spell): ?int
    {
        $duration = $spell->duration;

        // Amplify Magic
        if ($this->isSpellActive($dominion, 'amplify_magic')) {
            if ($this->spellHelper->isSelfSpell($spell) && !$spell->cooldown) {
                $duration = round($duration * (1 + $dominion->getSpellPerkValue('self_spell_duration') / 100));
            }
        }

        return $duration;
    }

    /**
     * Calculates the final value of a spell perk for a $dominion.
     *
     * @param Dominion $dominion
     * @param string $key
     * @return float
     */
    public function resolveSpellPerk(Dominion $dominion, string $key): float
    {
        $selfPerkValue = $dominion->getSpellPerkValue($key, ['self']);
        $hostilePerkValue = $dominion->getSpellPerkValue($key, ['hostile']);
        $warPerkValue = $dominion->getSpellPerkValue($key, ['war']);
        $friendlyPerkValue = $dominion->getSpellPerkValue($key, ['friendly']);
        $statusEffectPerkValue = $dominion->getSpellPerkValue($key, ['effect']);

        return ($selfPerkValue + $hostilePerkValue + $warPerkValue + $friendlyPerkValue + $statusEffectPerkValue);
    }
}

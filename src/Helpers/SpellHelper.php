<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Race;
use OpenDominion\Models\Spell;
use OpenDominion\Models\SpellPerkType;

class SpellHelper
{
    /**
     * Returns spell by key its key.
     *
     * @param string $key
     * @return Spell
     */
    public function getSpellByKey(string $key): Spell
    {
        return Spell::firstWhere('key', $key);
    }

    /**
     * Returns available spells by race and category.
     *
     * @param string|string[] $perks
     * @param Race|null $race
     * @return Collection
     */
    public function getSpells(Race $race = null, string $category = null): Collection
    {
        $spells = Spell::with('perks')->active()->get();
        if ($race !== null) {
            $spells = $spells->filter(function ($spell) use ($race) {
                if (empty($spell->races) || in_array($race->key, $spell->races)) {
                    return true;
                }
                return false;
            });
        }
        if ($category !== null) {
            $spells = $spells->where('category', $category);
        }
        return $spells->keyBy('key');
    }

    /**
     * Returns spells with matching perk type(s). Optionally show only racial spells.
     *
     * @param string|string[] $perks
     * @param Race|null $race
     * @return Collection
     */
    public function getSpellsWithPerk($perks, Race $race = null): Collection
    {
        if (!is_array($perks)) {
            $perks = [$perks];
        }

        $spells = SpellPerkType::with('spells')
            ->whereIn('key', $perks)
            ->get()
            ->flatMap(function ($perkType) {
                return $perkType->spells;
            });

        if ($race !== null) {
            return $spells->filter(function ($spell) use ($race) {
                if (in_array($race->key, $spell->races)) {
                    return true;
                }
                return false;
            });
        }
        return $spells;
    }

    public function isSelfSpell(Spell $spell): bool
    {
        return $spell->category == 'self';
    }

    public function isRacialSelfSpell(Spell $spell): bool
    {
        return $spell->category == 'self' && !empty($spell->races);
    }

    public function isOffensiveSpell(Spell $spell): bool
    {
        return $spell->category !== 'self';
    }

    public function isInfoOpSpell(Spell $spell): bool
    {
        return $spell->category == 'info';
    }

    public function isHostileSpell(Spell $spell): bool
    {
        return !in_array($spell->category, ['info', 'self']);
    }

    public function isBlackOpSpell(Spell $spell): bool
    {
        return $spell->category == 'hostile';
    }

    public function isWarSpell(Spell $spell): bool
    {
        return $spell->category == 'war';
    }

    public function getSpellPerkStrings()
    {
        return [
            // Military related
            'defense' => '%+d%% defensive power',
            'offense' => '%+d%% offensive power',
            'offense_from_barren_land' => '+1%% offensive power for every 1%% barren land (max %+d%%)',
            'auto_rezone_forest'=> '%d%% of captured land re-zoned into forest',
            'auto_rezone_water'=> '%d%% of captured land re-zoned into water',
            'conversion_rate' => '%+d%% conversion rate',
            'convert_werewolves' => 'Werewolves convert enemy peasants into Werewolves (up to one for every %d sent on attack)',
            'kills_immortal' => 'Can kill spirits and the undead',
            'ignore_draftees' => 'Enemy draftees do not participate in battle',
            'spreads_plague' => 'afflicts your enemies with Plague',
            'upgrade_swordsmen' => '%d%% of surviving Swordsmen return from battle as Spellblades (75%%+ range only)',

            // Casualties related
            'fewer_casualties' => '%+d%% fewer casualties',
            'fewer_casualties_offense' => '%+d%% fewer casualties on offense',

            // Info ops
            'clear_sight' => 'Reveal status screen',
            'revelation' => 'Reveal active spells',
            'vision' => 'Reveal technology',

            // Logistics
            'population_growth' => '%+d%% population growth',
            'rezone_cost' => '%+d%% rezoning platinum cost',

            // Resource related
            'boat_production' => '%+d%% boat production',
            'food_production' => '%+d%% food production',
            'gem_production' => '%+d%% gem production',
            'lumber_production' => '%+d%% lumber production',
            'ore_production' => '%+d%% ore production',
            'platinum_production' => '%+d%% platinum production',
            'platinum_production_raw' => '%+d alchemy platinum production',

            // Wizard related
            'energy_mirror' => '20%% chance to reflect incoming offensive spells',
            'fools_gold' => 'Platinum theft protection',
            'surreal_perception' => 'Reveals the dominion casting spells or committing spy ops against you',
            'convert_military_spies_to_military_draftees' => 'Turns %.2f%% of enemy spies into draftees',
            'convert_military_spies_to_self_military_unit3' => 'Converts %.2f%% of enemy wizards into Progeny',
            'destroy_peasants' => 'Kills %.2f%% peasants',
            'destroy_resource_food' => 'Destroys %.2f%% crops',
            'destroy_improvement_science' => 'Destroys %.2f%% science',
            'destroy_improvement_keep' => 'Destroys %.2f%% keep',
            'destroy_improvement_forges' => 'Destroys %.2f%% forges',
            'destroy_improvement_walls' => 'Destroys %.2f%% walls',
            'enemy_earthquake_damage' => 'Ore production immune to Earthquake',
            'wonder_damage' => 'Deals damage to wonders',
        ];
    }

    public function getSpellDescription(Spell $spell, string $separator = ', '): string
    {
        $perkTypeStrings = $this->getSpellPerkStrings();

        $perkStrings = [];
        foreach ($spell->perks as $perk) {
            if (isset($perkTypeStrings[$perk->key])) {
                $perkValue = (float)$perk->pivot->value;
                $perkStrings[] = vsprintf($perkTypeStrings[$perk->key], $perkValue);
            }
        }

        if ($spell->cooldown) {
            $perkStrings[] = "{$spell->cooldown} hour recharge";
        }

        return implode($separator, $perkStrings);
    }

    public function getSpellRaces(Spell $spell, string $separator = ', '): string
    {
        $raceStrings = [];
        foreach ($spell->races as $race) {
            $raceStrings[] = ucwords(str_replace('-', ' ', str_replace('-rework', ' ', $race)));
        }

        return implode($separator, $raceStrings);
    }
}

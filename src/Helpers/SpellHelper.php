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
     * @param Race|null $race
     * @param string|null $category
     * @return Collection
     */
    public function getSpells(Race $race = null, string $category = null): Collection
    {
        $spells = Spell::with('perks')
            ->active()
            ->get()
            ->map(function ($spell) {
                $spell->racial = ($spell->races !== []);
                return $spell;
            });

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

        return $spells->keyBy('key')->sortBy('races');
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
                if (!$spell->active) {
                    return false;
                }
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
        return !in_array($spell->category, ['self', 'friendly']);
    }

    public function isInfoOpSpell(Spell $spell): bool
    {
        return $spell->category == 'info';
    }

    public function isFriendlySpell(Spell $spell): bool
    {
        return $spell->category == 'friendly';
    }

    public function isHostileSpell(Spell $spell): bool
    {
        return !in_array($spell->category, ['info', 'self', 'friendly']);
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
            'defense' => '%+g%% defensive power',
            'offense' => '%+g%% offensive power',
            'offense_from_barren_land' => '+1%% offensive power for every 1%% barren land (max %+g%%)',
            'auto_rezone_forest'=> '%d%% of captured land re-zoned into forest',
            'auto_rezone_water'=> '%d%% of captured land re-zoned into water',
            'conversion_rate' => '%+g%% conversion rate',
            'conversions_range' => 'Conversions increased by %d%% against dominions in The Graveyard under 75%% of your size',
            'convert_werewolves' => 'Werewolves convert enemy peasants into Werewolves (up to one for every %d sent on attack)',
            'kills_immortal' => 'Can kill spirits and the undead',
            'ignore_draftees' => 'Enemy draftees do not participate in battle',
            'sacrifice_peasants' => 'Sacrifice %g%% of your peasants',
            'spreads_plague' => 'afflicts your enemies with Plague',
            'upgrade_swordsmen' => '%d%% of surviving Swordsmen return from battle as Spellblades (75%%+ range only)',
            'upgrade_specs' => 'Sacrifice Skeletons and Ghouls to summon Death Knights and Necromancers (2 plus 1 per 1000 acres, hourly)',

            // Casualties related
            'casualties' => '%d%% casualties',
            'casualties_offense' => '%d%% offensive casualties',
            'casualties_defense' => '%d%% defensive casualties',

            // Info ops
            'clear_sight' => 'Reveal status screen',
            'disclosure' => 'Reveal heroes',
            'revelation' => 'Reveal active spells',
            'vision' => 'Reveal technology',

            // Logistics
            'military_cost_elite' => '%+g%% elite military training cost',
            'population_growth' => '%+g%% population growth',
            'rezone_cost' => '%+g%% rezoning platinum cost',

            // Resource related
            'boat_production' => '%+g%% boat production',
            'food_production' => '%+g%% food production',
            'gem_production' => '%+g%% gem production',
            'lumber_production' => '%+g%% lumber production',
            'mana_production' => '%+g%% mana production',
            'ore_production' => '%+g%% ore production',
            'platinum_production' => '%+g%% platinum production',
            'platinum_production_raw' => '%+d alchemy platinum production',

            // Wizard related
            'energy_mirror' => '%d%% chance to reflect incoming offensive spells',
            'enemy_spell_damage' => '%+g%% enemy spell damage',
            'enemy_spell_duration' => '%+g enemy spell duration',
            'spell_reflect' => 'Reflects the next incoming Black Op or War spell',
            'fools_gold' => 'Platinum theft protection',
            'surreal_perception' => 'Reveals the dominion casting spells or committing spy ops against you',
            'self_spell_cost' => 'Increases the mana cost of your next non-cooldown self spell by %d%%',
            'self_spell_duration' => 'but increases duration by %d%%',
            'convert_military_spies_to_military_draftees' => 'Turns %g%% of enemy spies into draftees',
            'convert_peasants_to_self_military_unit3' => 'Kills %g%% of enemy peasants, converting 5%% into Progeny',
            'apply_burning' => 'chance to inflict Burning if at war',
            'destroy_peasants' => 'Kills %g%% unprotected peasants',
            'destroy_resource_food' => 'Destroys %g%% crops',
            'destroy_improvement_science' => 'Destroys %g%% science',
            'destroy_improvement_keep' => 'Destroys %g%% keep',
            'destroy_improvement_forges' => 'Destroys %g%% forges',
            'destroy_improvement_walls' => 'Destroys %g%% walls',
            'ore_production_damage' => 'Ore production immune to Earthquake',
            'food_decay' => '%+g%% food decay',
            'lumber_decay' => '%+g%% lumber rot',
            'mana_decay' => '%+g%% mana drain',
            'spy_losses' => '%s%% spy losses on failed operations',
            'spy_power' => '%+g%% spy power',
            'spy_power_defense' => '%+g%% defensive spy power',
            'wizard_power' => '%+g%% wizard power',
            'wizard_power_defense' => '%+g%% defensive wizard power',
            'wonder_damage' => 'Deals damage to wonders',
            'explore_cost_wizard_mastery' => 'Exploring platinum cost reduced by 1%% per %d Wizard Mastery',
            'spell_refund' => 'Failed spells refund %d%% of their mana cost',
            'fixed_population_growth' => 'Population growth is slowed based on your vulnerability to fireball',
            'apply_rejuvenation' => 'Applies Rejuvenation upon expiration',
            'immune_burning' => 'Immune to Burning',
            'war_cancels' => 'Cancelled if this realm declares war',
            'cancels_gaias_light' => 'Cancels and cancelled by Gaia\'s Light',
            'cancels_gaias_shadow' => 'Cancels and cancelled by Gaia\'s Shadow',
            'cancels_midas_touch' => 'Cancels and cancelled by Midas Touch',
        ];
    }

    public function getSpellDescription(Spell $spell, string $separator = ', '): string
    {
        $perkTypeStrings = $this->getSpellPerkStrings();

        $perkStrings = [];
        foreach ($spell->perks as $perk) {
            if (isset($perkTypeStrings[$perk->key])) {
                $perkValue = (float)$perk->pivot->value;
                $perkStrings[] = sprintf($perkTypeStrings[$perk->key], $perkValue);
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

    public function getCategoryString(string $category) {
        $categories = [
            'info' => 'Information',
            'friendly' => 'Friendly',
            'hostile' => 'Offensive',
            'war' => 'War',
            'wonder' => 'Wonder',
            'self' => 'Self',
            'effect' => 'Status Effect',
        ];

        return $categories[$category];
    }

    public function getSpellType(Spell $spell) {
        return $this->getCategoryString($spell->category);
    }

    public function obfuscateInfoOps(array $infoOps) {
        if (isset($infoOps['revelation'])) {
            foreach ($infoOps['revelation']['spells'] as $key => $spell) {
                $infoOps['revelation']['spells'][$key]['cast_by_dominion_id'] = null;
                $infoOps['revelation']['spells'][$key]['cast_by_dominion_name'] = null;
                $infoOps['revelation']['spells'][$key]['cast_by_dominion_realm_number'] = null;
            }
        }
        return $infoOps;
    }
}

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
    public function getSpellByKey(string $key): ?Spell
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
                $spell->racial = ($spell->races !== [] && $spell->races !== ['chaos-league']);
                return $spell;
            });

        if ($race !== null) {
            $spells = $spells->filter(function ($spell) use ($race) {
                if (!$spell->racial || in_array($race->key, $spell->races)) {
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
            'offense_from_pairing_demon' => 'Offense increased by 1 for each Imp and Archdemon paired on attack',
            'apply_corruption' => 'Applies Corruption upon attack',
            'auto_rezone_forest'=> '%d%% of captured land re-zoned into forest',
            'auto_rezone_water'=> '%d%% of captured land re-zoned into water',
            'cancels_immortal' => 'Military units lose all casualty reductions (including immortality)',
            'conversion_rate' => '%+g%% conversion rate',
            'conversions_range' => 'Conversions increased by %d%% against dominions in The Graveyard under 75%% of your size',
            'convert_vampires' => 'Bloodreavers convert additional Kindred equal to %g%% of units sent',
            'convert_werewolves' => 'Werewolves convert enemy peasants into Werewolves (up to one for every %d sent on attack)',
            'kills_immortal' => 'Can kill immortal units',
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
            'construction_cost' => '%+g%% construction costs',
            'military_cost_elite' => '%+g%% elite military training cost',
            'population_growth' => '%+g%% population growth',
            'rezone_cost' => '%+g%% rezoning platinum cost',

            // Resource related
            'boat_production' => '%+g%% boat production',
            'food_consumption' => '%+g%% food consumption',
            'food_production' => '%+g%% food production',
            'gem_production' => '%+g%% gem production',
            'lumber_production' => '%+g%% lumber production',
            'mana_production' => '%+g%% mana production',
            'ore_production' => '%+g%% ore production',
            'platinum_production' => '%+g%% platinum production',
            'platinum_production_raw' => '%+d alchemy platinum production',
            'wizard_guilds_produce_military_unit3' => 'Each wizard guild produces %g Adepts per hour',
            'wizard_guild_mana_production_raw' => '%+g mana production from wizard guilds',

            // Spy/Wizard related
            'energy_mirror' => '%d%% chance to reflect incoming offensive spells',
            'enemy_espionage_chance' => '%+g%% chance of causing hostile spy operations to fail',
            'enemy_fireball_damage' => '%+g%% enemy fireball damage',
            'enemy_lightning_bolt_damage' => '%+g%% enemy lightning bolt damage',
            'enemy_spell_chance' => '%+g%% chance of causing hostile spells to fail',
            'enemy_spell_damage' => '%+g%% enemy spell damage',
            'enemy_spell_duration' => '%+g enemy spell duration',
            'spell_reflect' => 'Reflects the next incoming Black Op or War spell',
            'fools_gold' => 'Platinum theft protection',
            'surreal_perception' => 'Reveals the dominion casting spells or committing spy ops against you',
            'self_spell_cost' => 'Increases the mana cost of your next non-cooldown self spell by %d%%',
            'self_spell_duration' => 'but increases duration by %d%%',
            'convert_military_spies_to_military_draftees' => 'Turns %g%% of enemy spies into draftees',
            'convert_peasants_to_self_military_unit3' => 'Kills %g%% of enemy peasants, converting 5%% into Progeny',
            'convert_peasants_to_self_military_unit1' => '%g%% of peasants die from disease each hour and return as Zombies',
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
            'martyrdom' => 'Spy and wizard cost reduced by 1%% per 15 prestige (max 50%%)',
            'spy_cost' => '%+g%% cost of spies',
            'spy_losses' => '%s%% spy losses on failed operations',
            'spy_power' => '%+g%% spy power',
            'spy_power_defense' => '%+g%% defensive spy power',
            'wizard_cost' => '%+g%% cost of wizards',
            'wizard_power' => '%+g%% wizard power',
            'wizard_power_defense' => '%+g%% defensive wizard power',
            'wonder_damage' => 'Deals damage to wonders',
            'explore_cost_wizard_mastery' => 'Exploring platinum cost reduced by 1%% per %d Wizard Mastery (max 10%%)',
            'spell_refund' => 'Failed chaos spells refund %d%% of their strength and mana costs',
            'invalid_royal_guard' => 'Cannot be cast while in the Royal Guard',
            'apply_rejuvenation' => 'Applies Rejuvenation upon expiration',
            'immune_burning' => 'Immune to Burning',
            'immune_lightning_storm' => 'Immune to Lightning Storm',
            'lightning_storm' => 'Lightning Bolt deals an additional %g%% temporary damage (until this expires)',
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

    public function getChaosSpellName(Spell $spell): string
    {
        switch ($spell->key) {
            case 'fireball':
                return 'Chaos Fireball';
            case 'lightning_bolt':
                return 'Chaos Lightning';
            case 'disband_spies':
                return 'Chaos Disband';
        }
    }

    public function getChaosSpellDescription(Spell $spell): string
    {
        switch ($spell->key) {
            case 'fireball':
                return 'Kills 6% unprotected peasants';
            case 'lightning_bolt':
                return 'Temporarily destroys 0.3% science, keep, forges, walls';
            case 'disband_spies':
                return 'Turns 2% of spies into random resources for yourself';
        }
    }

    public function getSpellRaces(Spell $spell, string $separator = ', '): string
    {
        $raceStrings = [];
        foreach ($spell->races as $race) {
            if (!str_contains($race, '-legacy') && !in_array($race, ['dark-elf', 'kobold', 'nomad', 'spirit', 'undead'])) {
                $raceStrings[] = ucwords(str_replace('-', ' ', str_replace('-rework', ' ', $race)));
            }
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

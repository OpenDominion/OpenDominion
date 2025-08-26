<?php

namespace OpenDominion\Helpers;

class MiscHelper
{
    public function getResourceHelpString(string $resource): ?string {
        $helpStrings = [
            'platinum' => 'Produced via alchemies and peasants paying taxes.',
            'food' => 'Produced via farms and docks.<br>Each citizen (peasants and military) eats 0.25 bushels per hour.',
            'lumber' => 'Produced via lumberyards.<br>Used for constructing buildings.',
            'mana' => 'Produced via towers.<br>Used for casting spells.',
            'ore' => 'Produced via ore mines.<br>Used to train <i>some</i> units.',
            'gems' => 'Produced via diamond mines.<br>Only used for improvements.',
            'tech' => 'Produced via schools or invasions.<br>Used to gain techs.',
            'boats' => 'Produced via docks.<br>Used by <i>most</i> races during invasions.<br>Each boat carries 30 units (40 for Kobold).',
        ];

        return $helpStrings[$resource] ?: null;
    }

    public function getGeneralHelpString(string $type) {
        $helpStrings = [
            'peasants' => 'Peasants are the non-military part of your population. They pay taxes and get drafted into military service.',
            'employment' => 'Each employed peasant pays 2.7 platinum per hour in taxes.',
            'networth' => 'Used to determine power of a dominion.<br>Buildings, land, and units give networth.',
            'prestige' => 'Gained via invasion.<br>Every point of prestige increases offensive power, maximum population, and food production by 0.01%.',
            'morale' => 'Morale below 100% gives a defensive penalty.<br>Morale is lowered by exploring and invading.',
            'spy_mastery' => 'Gained and lost via war operations.<br>Every 100 mastery awards +0.2% spy strength recovery (max +2%) and -5% spy losses (max -50%).',
            'wizard_mastery' => 'Gained and lost via war operations.<br>Every 100 mastery awards +0.2% wizard strength recovery (max +2%) and -2% spell mana costs (max -20%).',
            'resilience' => 'Gained by victims of the magic snare operation.<br>Increases wizard strength recovery while under 30% by 1% per 100 resilience.',
            'spa' => 'Raw Spy Ratio.',
            'wpa' => 'Raw Wizard Ratio.<br>Used to calculate Fireball protection and offense of Ice Elementals.',
        ];

        return $helpStrings[$type] ?: null;
    }
}

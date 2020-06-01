<?php

namespace OpenDominion\Helpers;

class MiscHelper
{
    public function getResourceHelpString(string $resource): ?string {
        $helpStrings = [
            'platinum' => 'Produced via alchemies and peasants paying taxes.<br>Each peasant pays 2.7p/h in taxes.',
            'food' => 'Produced via farms and docks.<br>Each citizen (peasants and military) eat 0.25 bushels per hour',
            'lumber' => 'Produced via lumberyards.<br>Used for constructing buildings.',
            'mana' => 'Produced via towers.<br>Used for casting spells.',
            'ore' => 'Produced via ore mines.<br>Used to train <i>some</i> units.',
            'gems' => 'Produced via diamond mines.<br>Only used for investing.',
            'tech' => 'Produced via schools or invasions.<br>Used to gain techs.',
            'boats' => 'Produced via docks.<br>Used by <i>most</i> races during invasions.<br>Each boat carries 30 units (40 for Kobold).',
        ];

        return $helpStrings[$resource] ?: null;
    }

    public function getGeneralHelpString(string $type) {

    }
}
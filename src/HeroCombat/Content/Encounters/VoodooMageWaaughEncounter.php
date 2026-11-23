<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\EncounterContext;

/**
 * Dragon's Miasma confuses heroes who aren't Focused when it is exhaled; Bone Splinter
 * punishes the confused. Scholars and sorcerers are confused less often.
 */
class VoodooMageWaaughEncounter extends AbstractEncounter
{
    /** @var string[] hero classes that resist confusion */
    public const CLEAR_MINDED_CLASSES = ['scholar', 'sorcerer'];

    public function key(): string
    {
        return 'voodoo_mage_waaugh';
    }

    public function name(): string
    {
        return 'Voodoo Mage Waaugh';
    }

    public function source(): string
    {
        return 'Raid (Something is Wrong in Throk-Bak)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'voodoo_mage_waaugh', 'name' => 'Voodoo Mage Waaugh'],
        ];
    }

    public function playerEffects(CombatantState $player, array $heroClasses): array
    {
        return array_intersect(self::CLEAR_MINDED_CLASSES, $heroClasses) !== [] ? ['clear_minded' => []] : [];
    }

    public function victoryMessage(): ?string
    {
        return 'Waaugh slumps to the cavern floor, and for a moment his eyes are his own. Then something red and furious tears loose from his body and climbs toward the night sky...';
    }
}

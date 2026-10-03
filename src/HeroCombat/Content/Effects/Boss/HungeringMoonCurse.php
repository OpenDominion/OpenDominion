<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * Rex Lunae may begin casting at the end of any turn and finishes the next. Above the
 * frenzy threshold it leaves a turn between curses so a limited Cleanse is always ready.
 */
class HungeringMoonCurse extends AbstractTelegraph
{
    public const TELEGRAPH_CHANCE = 0.5;
    public const FRENZY_THRESHOLD = 80;

    public function key(): string
    {
        return 'hungering_moon_curse';
    }

    public function name(): string
    {
        return 'Curse of the Hungry Moon';
    }

    public function description(EffectInstance $instance): string
    {
        return 'If not cleansed immediately, permanently reduces the target\'s maximum health by one third. Defense is reduced by 5 while casting.';
    }

    protected function moves(): array
    {
        return ['hungering_moon'];
    }

    protected function tells(): array
    {
        return ['hungering_moon' => '{actor} begins casting the Curse of the Hungry Moon.'];
    }

    protected function period(): int
    {
        return 1;
    }

    protected function telegraphOn(): int
    {
        return 0;
    }

    protected function performOn(): int
    {
        return 0;
    }

    protected function chooseMove(EffectInstance $instance, CombatantState $owner, Battle $battle): ?string
    {
        $frenzied = $owner->currentHealth < self::FRENZY_THRESHOLD;
        $justCursed = $owner->lastAction === 'hungering_moon';

        if (!$frenzied && $justCursed) {
            return null;
        }

        return $battle->random->chance(self::TELEGRAPH_CHANCE) ? 'hungering_moon' : null;
    }
}

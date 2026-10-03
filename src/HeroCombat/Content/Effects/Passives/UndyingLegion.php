<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class UndyingLegion extends AbstractPassive
{
    public const IMMUNE_DEFENSE = 999;

    public function key(): string
    {
        return 'undying_legion';
    }

    public function name(): string
    {
        return 'Undying Legion';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Immune to damage while any minions are alive.';
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        if ($battle->alliesOf($subject, includeSelf: false) === []) {
            return [];
        }

        return [Modifier::override(Stat::Defense, self::IMMUNE_DEFENSE)];
    }
}

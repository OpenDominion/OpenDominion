<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class Frozen extends AbstractEffect
{
    public function key(): string
    {
        return 'frozen';
    }

    public function name(): string
    {
        return 'Frozen';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Attack, counter, and recover are reduced to 0 this turn.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Debuff;
    }

    public function defaultDuration(): ?int
    {
        return 1;
    }

    public function tags(): array
    {
        return [CombatTag::Frost, CombatTag::Frozen];
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        return [
            Modifier::override(Stat::Attack, 0),
            Modifier::override(Stat::Counter, 0),
            Modifier::override(Stat::Recover, 0),
        ];
    }
}

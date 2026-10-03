<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

class Necromancy extends AbstractCadence
{
    public function key(): string
    {
        return 'necromancy';
    }

    public function name(): string
    {
        return 'Summon';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Summons a Skeleton Warrior every ' . $this->interval($instance) . ' turns.';
    }

    protected function abilityKey(): string
    {
        return 'summon_skeleton';
    }

    protected function defaultInterval(): int
    {
        return 4;
    }

    protected function warning(): string
    {
        return 'A summoning circle begins to glow around {actor}.';
    }
}

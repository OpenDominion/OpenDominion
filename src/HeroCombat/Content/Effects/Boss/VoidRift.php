<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

class VoidRift extends AbstractCadence
{
    public function key(): string
    {
        return 'void_rift';
    }

    public function name(): string
    {
        return 'Summon Golem';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Summons a Void Golem every ' . $this->interval($instance) . ' turns.';
    }

    protected function abilityKey(): string
    {
        return 'summon_golem';
    }

    protected function defaultInterval(): int
    {
        return 4;
    }

    protected function warning(): string
    {
        return 'A void rift begins to tear open near {actor}.';
    }
}

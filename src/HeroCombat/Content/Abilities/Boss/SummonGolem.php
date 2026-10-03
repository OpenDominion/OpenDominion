<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

class SummonGolem extends AbstractSummon
{
    public function key(): string
    {
        return 'summon_golem';
    }

    public function name(): string
    {
        return 'Summon Golem';
    }

    protected function template(): string
    {
        return 'golem';
    }

    public function messages(): array
    {
        return ['summon' => '{actor} tears a rift and summons a Void Construct!'];
    }
}

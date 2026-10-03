<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

class SummonSkeleton extends AbstractSummon
{
    public function key(): string
    {
        return 'summon_skeleton';
    }

    public function name(): string
    {
        return 'Summon Skeleton';
    }

    protected function template(): string
    {
        return 'skeleton_warrior';
    }

    public function messages(): array
    {
        return ['summon' => '{actor} has summoned a Skeleton Warrior.'];
    }
}

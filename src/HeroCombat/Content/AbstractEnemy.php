<?php

namespace OpenDominion\HeroCombat\Content;

use OpenDominion\HeroCombat\Contracts\EnemyTemplate;

abstract class AbstractEnemy implements EnemyTemplate
{
    public const BASIC_ABILITIES = ['attack', 'defend', 'focus', 'counter', 'recover'];

    /**
     * Basic abilities plus anything the enemy adds.
     */
    public function abilities(): array
    {
        return array_values(array_unique(array_merge(self::BASIC_ABILITIES, $this->extraAbilities())));
    }

    /**
     * @return string[]
     */
    protected function extraAbilities(): array
    {
        return [];
    }

    public function effects(): array
    {
        return [];
    }

    public function ai(): string
    {
        return 'balanced';
    }
}

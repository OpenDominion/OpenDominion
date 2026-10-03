<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

class TomeOfPowerChapters extends AbstractPhaseCycle
{
    public function key(): string
    {
        return 'tome_of_power';
    }

    public function name(): string
    {
        return 'Tome of Power';
    }

    protected function turnsPerPhase(): int
    {
        return 3;
    }

    protected function phases(): array
    {
        return [
            1 => ['name' => 'Chapter of Blood', 'self' => ['weakened'], 'allies' => ['lifesteal'], 'message' => '{actor} turns to the Chapter of Blood!'],
            2 => ['name' => 'Chapter of Protection', 'self' => ['elusive'], 'allies' => ['arcane_shield'], 'message' => '{actor} turns to the Chapter of Protection!'],
            3 => ['name' => 'Chapter of Destruction', 'self' => ['weakened'], 'allies' => ['crushing_blow'], 'message' => '{actor} turns to the Chapter of Destruction!'],
            4 => ['name' => 'Chapter of Vengeance', 'self' => ['elusive'], 'allies' => ['retribution'], 'message' => '{actor} turns to the Chapter of Vengeance!'],
        ];
    }
}

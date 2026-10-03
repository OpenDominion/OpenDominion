<?php

namespace OpenDominion\HeroCombat\Contracts;

interface EnemyTemplate
{
    public function key(): string;

    public function name(): string;

    /** @return array<string, int> keyed by Stat value */
    public function stats(): array;

    /** @return string[] active ability keys */
    public function abilities(): array;

    /** @return array<string, array<string, mixed>> innate effect key => instance data */
    public function effects(): array;

    public function ai(): string;
}

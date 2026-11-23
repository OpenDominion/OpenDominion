<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

/**
 * Practice for 2v2: your hero and a realmmate's against an Evil Twin of each, copying their
 * combat stats.
 */
class EvilTwinPairEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'evil_twin_2v2';
    }

    public function name(): string
    {
        return 'Evil Twin (2v2)';
    }

    public function source(): string
    {
        return 'Practice';
    }

    public function minPartySize(): int
    {
        return 2;
    }

    public function maxPartySize(): int
    {
        return 2;
    }

    public function roster(EncounterContext $context): array
    {
        if ($context->party === []) {
            return [['template' => 'evil_twin', 'name' => 'Evil Twin', 'stats' => $context->leaderStats]];
        }

        return array_map(fn (array $hero) => [
            'template' => 'evil_twin',
            'name' => "{$hero['name']}'s Evil Twin",
            'stats' => $hero['stats'],
        ], $context->party);
    }
}

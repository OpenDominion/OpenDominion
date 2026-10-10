<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Content\Enemies\Planewalker;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\EncounterContext;

/**
 * Each prior realm victory wounds the Planewalker: -10% health and evasion (to half at
 * most) and one more turn between golem summons.
 */
class PlanewalkerEncounter extends AbstractEncounter
{
    public const WOUND_PER_WIN = 0.1;
    public const MAX_WOUND = 0.5;
    public const BASE_SUMMON_INTERVAL = 4;

    /** @var array<string, string> hero class => ability granted for this fight */
    public const CLASS_GRANTS = [
        'infiltrator' => 'shadow_strike',
        'sorcerer' => 'great_flood',
        'engineer' => 'demolish',
    ];

    public function key(): string
    {
        return 'planewalker';
    }

    public function name(): string
    {
        return 'The Planewalker';
    }

    public function source(): string
    {
        return 'Raid (Planewalker Incursion)';
    }

    public function roster(EncounterContext $context): array
    {
        $entry = ['template' => 'planewalker', 'name' => 'The Planewalker'];

        if ($context->priorWins > 0) {
            $multiplier = max(1 - self::MAX_WOUND, 1 - $context->priorWins * self::WOUND_PER_WIN);
            $stats = (new Planewalker())->stats();
            $entry['stats'] = [
                'health' => max(1, (int) round($stats['health'] * $multiplier)),
                'evasion' => max(1, (int) round($stats['evasion'] * $multiplier)),
            ];
            $entry['effects'] = ['void_rift' => ['interval' => self::BASE_SUMMON_INTERVAL + $context->priorWins]];
        }

        return [$entry];
    }

    public function playerGrants(CombatantState $player, array $heroClasses): array
    {
        return array_values(array_intersect_key(self::CLASS_GRANTS, array_flip($heroClasses)));
    }

    public function victoryMessage(): ?string
    {
        return 'The Planewalker howls in agony as its form becomes unstable. It tears a rift in the fabric of reality and retreats across the planes — grievously wounded, but not destroyed...';
    }
}

<?php

namespace OpenDominion\HeroCombat\Persistence;

use OpenDominion\Exceptions\GameException;
use OpenDominion\HeroCombat\Contracts\CombatantSpawner;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\BattleState;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Events\EventType;
use OpenDominion\HeroCombat\Engine\Intent;
use OpenDominion\HeroCombat\Engine\Random\SeededRandomSource;
use OpenDominion\HeroCombat\Engine\TurnResolver;
use OpenDominion\HeroCombat\Registry\CombatRegistry;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroBattleAction;

/**
 * Re-simulates a stored battle from its initial snapshot, seed and the actions players
 * queued, and compares the result with the stored combat log. Useful for debugging
 * reports and for checking that refactors do not change outcomes.
 */
class BattleReplayer
{
    public function __construct(
        protected CombatRegistry $registry,
        protected StateSnapshot $snapshot = new StateSnapshot(),
    ) {
    }

    /**
     * @return array{turns: int, matches: bool, mismatches: array<int, array{turn: int, expected: string, actual: string}>, battle: Battle}
     */
    public function replay(HeroBattle $heroBattle): array
    {
        if ($heroBattle->initial_state === null) {
            throw new GameException('This battle has no initial snapshot and cannot be replayed.');
        }

        $state = $this->snapshot->restore(json_decode($heroBattle->initial_state, true), $heroBattle->id);
        $battle = new Battle($state, $this->registry, new SeededRandomSource($state->seed), $this->spawner($heroBattle, $state));

        $rows = HeroBattleAction::query()->where('hero_battle_id', $heroBattle->id)->orderBy('id')->get();
        $queued = $this->queuedIntents($rows);
        $expected = $rows->groupBy('turn')->map(fn ($turnRows) => $this->text($turnRows->pluck('description')->all()));

        $resolver = new TurnResolver();
        $mismatches = [];
        $turns = 0;

        while (!$state->finished && $state->turn <= $heroBattle->current_turn && $expected->has($state->turn)) {
            $turn = $state->turn;
            foreach ($battle->living() as $combatant) {
                $this->prime($combatant, $queued[$turn][$combatant->id] ?? null);
            }

            $battle->random = SeededRandomSource::forTurn($state->seed, $turn);
            $resolver->resolve($battle);
            $turns++;

            $actual = $this->text(array_map(fn ($entry) => $entry->description(), $battle->log->flush()));
            if ($actual !== $expected[$turn]) {
                $mismatches[] = ['turn' => $turn, 'expected' => $expected[$turn], 'actual' => $actual];
            }
        }

        return ['turns' => $turns, 'matches' => $mismatches === [], 'mismatches' => $mismatches, 'battle' => $battle];
    }

    /**
     * Humans who queued an action get exactly that action; everyone else uses AI or
     * forced intents, which are deterministic for the seed.
     */
    protected function prime(CombatantState $combatant, ?Intent $intent): void
    {
        if (!$combatant->isHuman()) {
            return;
        }

        $combatant->queue = $intent !== null ? [['ability' => $intent->abilityKey, 'target' => $intent->targetId]] : [];
        $combatant->automated = $intent === null;
    }

    /**
     * @return array<int, array<int, Intent>> turn => actor id => intent
     */
    protected function queuedIntents($rows): array
    {
        $intents = [];
        foreach ($rows as $row) {
            $selected = collect($row->events ?? [])->firstWhere('type', EventType::Selected->value);
            if ($selected === null || ($selected['source'] ?? null) !== Intent::SOURCE_QUEUE) {
                continue;
            }
            $intents[$row->turn][$selected['actor']] = new Intent($selected['actor'], $selected['ability'], $selected['target'], Intent::SOURCE_QUEUE);
        }

        return $intents;
    }

    /**
     * Summoned combatants receive the same ids they were given originally, in order.
     */
    protected function spawner(HeroBattle $heroBattle, BattleState $state): CombatantSpawner
    {
        $ids = $heroBattle->combatants()->orderBy('id')->pluck('id')
            ->reject(fn ($id) => isset($state->combatants[$id]))
            ->values()
            ->all();

        return new class($ids) implements CombatantSpawner {
            public function __construct(private array $ids)
            {
            }

            public function spawn(BattleState $state, CombatantState $combatant): int
            {
                return array_shift($this->ids) ?? (max(array_keys($state->combatants)) + 1);
            }
        };
    }

    /**
     * @param string[] $descriptions
     */
    protected function text(array $descriptions): string
    {
        return trim(implode(' ', array_filter($descriptions, fn ($d) => $d !== '')));
    }
}

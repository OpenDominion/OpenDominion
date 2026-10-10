<?php

namespace OpenDominion\HeroCombat\Presentation;

use OpenDominion\HeroCombat\Engine\ActionValidator;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroCombatant;

/**
 * Read-only view model of a battle from one hero's point of view.
 */
class BattleView
{
    private ActionValidator $validator;

    public function __construct(
        public readonly HeroBattle $model,
        public readonly Battle $battle,
        public readonly ?CombatantState $viewer,
    ) {
        $this->validator = new ActionValidator($battle);
    }

    /**
     * The viewer's team first, then everyone else grouped by team.
     *
     * @return array<int, array{label: string, combatants: CombatantState[]}>
     */
    public function sides(): array
    {
        $teams = [];
        foreach ($this->battle->combatants() as $combatant) {
            $teams[$combatant->team][] = $combatant;
        }

        $viewerTeam = $this->viewer?->team;
        uksort($teams, fn ($a, $b) => [$a !== $viewerTeam, $a] <=> [$b !== $viewerTeam, $b]);

        $sides = [];
        foreach ($teams as $team => $combatants) {
            $sides[] = [
                'label' => $team === $viewerTeam ? 'Your side' : (count($teams) > 2 ? "Team {$team}" : 'Opponents'),
                'combatants' => $combatants,
            ];
        }

        return $sides;
    }

    public function isViewer(CombatantState $combatant): bool
    {
        return $this->viewer !== null && $combatant->id === $this->viewer->id;
    }

    public function model(CombatantState $combatant): ?HeroCombatant
    {
        return $this->model->combatants->firstWhere('id', $combatant->id);
    }

    /**
     * @return array<string, array{label: string, base: int, value: int, tooltip: string}>
     */
    public function stats(CombatantState $combatant): array
    {
        $tooltips = [
            'health' => 'Current and maximum health',
            'attack' => 'Attack damage, reduced by defense of opponent',
            'defense' => 'Reduce incoming attack damage by this amount, doubled while defending',
            'evasion' => 'Chance to evade an attack is equal to this percentage',
            'focus' => 'Focus increases attack damage by this amount',
            'counter' => 'Counter attack damage is increased by this amount',
            'recover' => 'Heal damage equal to this amount',
        ];

        $stats = [];
        foreach (Stat::cases() as $stat) {
            $stats[$stat->value] = [
                'label' => $stat->label(),
                'base' => $combatant->baseStat($stat),
                'value' => $this->battle->stat($combatant, $stat),
                'tooltip' => $tooltips[$stat->value],
            ];
        }

        return $stats;
    }

    /**
     * Visible effects applying to the combatant, from every scope.
     *
     * @return array<int, array{key: string, name: string, description: string, kind: string, turns: int|null, stacks: int}>
     */
    public function effects(CombatantState $combatant): array
    {
        $effects = [];
        foreach ($this->battle->effects->applicableTo($combatant) as [$effect, $instance]) {
            if (!$effect->visible()) {
                continue;
            }
            $effects[] = [
                'key' => $effect->key(),
                'name' => $effect->name(),
                'description' => $effect->description($instance),
                'kind' => $effect->kind()->value,
                'turns' => $instance->remainingTurns,
                'stacks' => $instance->stacks,
            ];
        }

        usort($effects, fn ($a, $b) => [$a['kind'] === EffectKind::Innate->value, $a['name']] <=> [$b['kind'] === EffectKind::Innate->value, $b['name']]);

        return $effects;
    }

    /**
     * Damage the combatant's shield will still absorb.
     */
    public function shield(CombatantState $combatant): int
    {
        return (int) ($this->battle->effects->find($combatant, 'shield')?->data['pool'] ?? 0);
    }

    public function canAct(): bool
    {
        $model = $this->viewer !== null ? $this->model($this->viewer) : null;

        return $model !== null && !$this->model->finished && $this->viewer->isAlive() && $model->time_bank > 0;
    }

    /**
     * The viewer's action menu.
     *
     * @return array<int, array{key: string, name: string, description: string, usable: bool, needsTarget: bool, hostile: bool, cooldown: int, charges: int|null}>
     */
    public function abilities(): array
    {
        if ($this->viewer === null) {
            return [];
        }

        $abilities = [];
        foreach ($this->viewer->abilities as $key) {
            if (!$this->battle->registry->hasAbility($key)) {
                continue;
            }
            $ability = $this->battle->registry->ability($key);
            if (!$ability->selectable()) {
                continue;
            }

            $abilities[] = [
                'key' => $key,
                'name' => $ability->name(),
                'description' => $ability->description(),
                'usable' => $this->canAct() && $this->validator->canQueue($this->viewer, $key),
                'needsTarget' => $ability->targetRule()->needsChosenTarget(),
                'hostile' => $ability->targetRule()->isHostile(),
                'cooldown' => $this->validator->turnsUntilReady($this->viewer, $key),
                'charges' => $this->validator->chargesRemaining($this->viewer, $key),
            ];
        }

        return $abilities;
    }

    /**
     * Valid targets the viewer could pick for an ability.
     *
     * @return CombatantState[]
     */
    public function targetsFor(string $abilityKey): array
    {
        if ($this->viewer === null) {
            return [];
        }

        $ability = $this->battle->registry->ability($abilityKey);

        return array_values(array_filter(
            $this->battle->living(),
            fn (CombatantState $c) => $this->battle->targets->isValidChoice($this->viewer, $ability, $c),
        ));
    }

    /**
     * @return array<int, array{turn: int, ability: string, target: string|null}>
     */
    public function queue(): array
    {
        if ($this->viewer === null) {
            return [];
        }

        $queue = [];
        foreach ($this->viewer->queue as $index => $queued) {
            $queue[] = [
                'turn' => $this->battle->turn() + $index,
                'ability' => $this->abilityName($queued['ability']),
                'target' => $this->battle->combatant($queued['target'] ?? null)?->name,
            ];
        }

        return $queue;
    }

    public function abilityName(string $key): string
    {
        return $this->battle->registry->hasAbility($key) ? $this->battle->registry->ability($key)->name() : ucwords(str_replace('_', ' ', $key));
    }

    /**
     * @return array<string, string> key => name
     */
    public function strategies(): array
    {
        $strategies = [];
        foreach ($this->battle->registry->strategies() as $key => $strategy) {
            if ($strategy->playerSelectable()) {
                $strategies[$key] = $strategy->name();
            }
        }

        return $strategies;
    }
}

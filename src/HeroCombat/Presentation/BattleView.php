<?php

namespace OpenDominion\HeroCombat\Presentation;

use OpenDominion\Helpers\HeroHelper;
use OpenDominion\HeroCombat\Engine\ActionValidator;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\BattleState;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Events\EventType;
use OpenDominion\HeroCombat\Engine\Events\MessageKind;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroCombatant;

/**
 * Read-only view model of a battle from one hero's point of view.
 */
class BattleView
{
    private ActionValidator $validator;

    /**
     * The combatant that represents the viewer (their own hero, else the first one they pilot).
     */
    public readonly ?CombatantState $viewer;

    /**
     * @param CombatantState[] $controlled combatants the viewing dominion pilots
     */
    public function __construct(
        public readonly HeroBattle $model,
        public readonly Battle $battle,
        public readonly array $controlled = [],
        ?int $viewerDominionId = null,
    ) {
        $this->validator = new ActionValidator($battle);

        $own = array_filter($controlled, fn (CombatantState $c) => $c->dominionId === $viewerDominionId);
        $this->viewer = reset($own) ?: (reset($controlled) ?: null);
    }

    /**
     * The viewer's team first, then everyone else grouped by team.
     *
     * @return array<int, array{team: int, label: string, combatants: CombatantState[]}>
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
                'team' => $team,
                'label' => $team === $viewerTeam ? 'Your side' : (count($teams) > 2 ? "Team {$team}" : 'Opponents'),
                'combatants' => $combatants,
            ];
        }

        return $sides;
    }

    /**
     * Whether the viewer can queue actions ahead. A turn resolves as soon as every living human
     * is ready, so queued actions only wait while a living human the viewer does not pilot has yet to act.
     */
    public function canQueueAhead(): bool
    {
        if ($this->viewer === null) {
            return false;
        }

        foreach ($this->battle->living() as $combatant) {
            if ($combatant->isPiloted() && !$this->isControlled($combatant)) {
                return true;
            }
        }

        return false;
    }

    public function isControlled(CombatantState $combatant): bool
    {
        foreach ($this->controlled as $controlled) {
            if ($controlled->id === $combatant->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * A realmmate's hero the viewer pilots.
     */
    public function isBorrowed(CombatantState $combatant): bool
    {
        return $this->isControlled($combatant) && $combatant->isHero() && $this->viewer !== null && $combatant->dominionId !== $this->viewer->dominionId;
    }

    /**
     * A templated ally piloted alongside a hero (2v2 PvP), whoever pilots it.
     */
    public function isAlly(CombatantState $combatant): bool
    {
        return $combatant->isPiloted() && !$combatant->isHero();
    }

    /**
     * Whether the combatant is the viewer's own hero.
     */
    protected function isOwnHero(CombatantState $combatant): bool
    {
        return $this->isControlled($combatant) && !$this->isBorrowed($combatant) && !$this->isAlly($combatant);
    }

    /**
     * Piloted combatants that still need orders, the viewer's own hero first.
     *
     * @return CombatantState[]
     */
    public function commandable(): array
    {
        if ($this->model->finished) {
            return [];
        }

        $living = array_values(array_filter($this->controlled, fn (CombatantState $c) => $c->isAlive()));
        usort($living, fn (CombatantState $a, CombatantState $b) => [!$this->isOwnHero($a), $a->id] <=> [!$this->isOwnHero($b), $b->id]);

        return $living;
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
     * @return array<int, array{sortsWithInnate: bool, key: string, name: string, description: string, kind: string, active: bool, turns: int|null, stacks: int}>
     */
    public function effects(CombatantState $combatant): array
    {
        $effects = [];
        foreach ($this->battle->effects->applicableTo($combatant) as [$effect, $instance]) {
            if (!$effect->visible()) {
                continue;
            }
            $effects[] = [
                'sortsWithInnate' => $effect->kind() === EffectKind::Innate,
                'key' => $effect->key(),
                'name' => $effect->name(),
                'description' => $effect->description($instance),
                'kind' => $effect->kind()->value,
                'active' => $effect->isActive($instance, $combatant, $this->battle),
                'turns' => $instance->remainingTurns,
                'stacks' => $instance->stacks,
            ];
        }

        usort($effects, fn ($a, $b) => [$a['sortsWithInnate'], $a['name']] <=> [$b['sortsWithInnate'], $b['name']]);

        return $effects;
    }

    /**
     * Damage the combatant's shield will still absorb.
     */
    public function shield(CombatantState $combatant): int
    {
        return (int) ($this->battle->effects->find($combatant, 'shield')?->data['pool'] ?? 0);
    }

    public function canAct(CombatantState $combatant): bool
    {
        $model = $this->model($combatant);

        return $model !== null && $this->isControlled($combatant) && !$this->model->finished && $combatant->isAlive() && $model->time_bank > 0;
    }

    /**
     * A piloted combatant's action menu.
     *
     * @return array<int, array{key: string, name: string, description: string, usable: bool, needsTarget: bool, hostile: bool, cooldown: int, charges: int|null, warning: string|null, onePerTeam: bool}>
     */
    public function abilities(CombatantState $combatant): array
    {
        $abilities = [];
        foreach ($combatant->abilities as $key) {
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
                'rulesNotes' => $ability->rulesNotes(),
                'usable' => $this->canAct($combatant) && $this->validator->canQueue($combatant, $key),
                'needsTarget' => $ability->targetRule()->needsChosenTarget(),
                'hostile' => $ability->targetRule()->isHostile(),
                'cooldown' => $this->validator->turnsUntilReady($combatant, $key),
                'charges' => $this->validator->chargesRemaining($combatant, $key),
                'warning' => $ability->warning($combatant),
                'onePerTeam' => $ability->onePerTeam(),
            ];
        }

        return $abilities;
    }

    /**
     * What would happen to a combatant if they used an ability this turn, from what they did
     * last turn, e.g. "Exposed if they Defend again". Lets players read an opponent's risks.
     *
     * @return string[]
     */
    public function warnings(CombatantState $combatant): array
    {
        $warnings = [];
        foreach ($combatant->abilities as $key) {
            if (!$this->battle->registry->hasAbility($key)) {
                continue;
            }
            $ability = $this->battle->registry->ability($key);
            $warning = $ability->warning($combatant);
            if ($warning !== null) {
                $warnings[] = "{$warning} if they {$ability->name()} again";
            }
        }

        return $warnings;
    }

    /**
     * Valid targets a piloted combatant could pick for an ability.
     *
     * @return CombatantState[]
     */
    public function targetsFor(CombatantState $combatant, string $abilityKey): array
    {
        $ability = $this->battle->registry->ability($abilityKey);

        return array_values(array_filter(
            $this->battle->living(),
            fn (CombatantState $c) => $this->battle->targets->isValidChoice($combatant, $ability, $c),
        ));
    }

    /**
     * @return array<int, array{turn: int, ability: string, target: string|null}>
     */
    public function queue(CombatantState $combatant): array
    {
        $queue = [];
        foreach ($combatant->queue as $index => $queued) {
            $queue[] = [
                'turn' => $this->battle->turn() + $index,
                'ability' => $this->abilityName($queued['ability']),
                'target' => $this->battle->combatant($queued['target'] ?? null)?->name,
            ];
        }

        return $queue;
    }

    /** Icons for the basic abilities; anything else uses a generic one. */
    public const ABILITY_ICONS = [
        'attack' => 'ra-sword',
        'defend' => 'ra-shield',
        'focus' => 'ra-on-target',
        'counter' => 'ra-reverse',
        'recover' => 'ra-health',
        'caltrops' => 'ra-bear-trap',
        'blade_flurry' => 'ra-daggers',
        'whirlwind' => 'ra-dervish-swords',
    ];

    /** How an ability used on an ally reads in the target picker. */
    public const ALLY_VERBS = [
        'focus' => 'Rally',
        'defend' => 'Guard',
        'recover' => 'Heal',
        'fortify' => 'Fortify',
        'caltrops' => 'Caltrops around',
    ];

    /**
     * The battle's mode and a short title: the encounter, else the teams' names.
     *
     * @return array{mode: string, title: string}
     */
    public function headline(): array
    {
        $mode = match ($this->battle->state->mode) {
            BattleState::MODE_PVP => 'PvP',
            BattleState::MODE_RAID => 'Raid',
            default => 'Practice',
        };

        if ($this->battle->encounter !== null) {
            return ['mode' => $mode, 'title' => 'vs ' . $this->battle->encounter->name()];
        }

        $teams = array_map(
            fn (array $side) => implode(' & ', array_map(fn (CombatantState $c) => $c->name, $side['combatants'])),
            $this->sides(),
        );

        return ['mode' => $mode, 'title' => implode(' vs ', $teams)];
    }

    /**
     * Seconds the viewer has left to act, from the first hero they pilot.
     */
    public function timeLeft(): ?int
    {
        foreach ($this->controlled as $combatant) {
            $model = $this->model($combatant);
            if ($model !== null) {
                return $model->timeLeft();
            }
        }

        return null;
    }

    /**
     * Whether the turn is waiting on the viewer: a living combatant they pilot has no order
     * and isn't automated. The page doesn't poll in this state, so it says so.
     */
    public function isWaitingOnYou(): bool
    {
        if ($this->model->finished) {
            return false;
        }

        foreach ($this->controlled as $combatant) {
            if ($combatant->isAlive() && !$combatant->isReady()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Living humans the viewer does not pilot who have yet to choose this turn.
     *
     * @return string[]
     */
    public function waitingOn(): array
    {
        return array_values(array_map(
            fn (CombatantState $c) => $c->name,
            array_filter($this->battle->living(), fn (CombatantState $c) => $c->isPiloted() && !$this->isControlled($c) && !$c->isReady()),
        ));
    }

    /**
     * Net health change per combatant over the last resolved turn.
     *
     * @return array<int, int>
     */
    public function healthChanges(): array
    {
        $latest = $this->model->turns->where('turn', '>', 0)->sortByDesc('turn')->first();
        $changes = [];
        foreach ($latest?->events ?? [] as $event) {
            if ($event['type'] === EventType::Damage->value) {
                $changes[$event['target']] = ($changes[$event['target']] ?? 0) - $event['amount'];
            } elseif ($event['type'] === EventType::Heal->value) {
                $changes[$event['target']] = ($changes[$event['target']] ?? 0) + $event['amount'];
            }
        }

        return array_filter($changes);
    }

    /**
     * Targets to pick between, or none when the ability takes no chosen target or has at most
     * one valid target. Yourself comes first for abilities used on allies.
     *
     * @return array<int, array{id: int, label: string, health: string, side: string}>
     */
    public function targetOptions(CombatantState $actor, string $abilityKey): array
    {
        $targets = $this->chosenTargets($actor, $abilityKey);
        if (count($targets) < 2) {
            return [];
        }

        usort($targets, fn (CombatantState $a, CombatantState $b) => [$a->id !== $actor->id, $a->id] <=> [$b->id !== $actor->id, $b->id]);

        return array_map(fn (CombatantState $target) => [
            'id' => $target->id,
            'label' => match (true) {
                $target->id === $actor->id => 'Yourself',
                $target->team === $actor->team && isset(self::ALLY_VERBS[$abilityKey]) => self::ALLY_VERBS[$abilityKey] . ' ' . $target->name,
                default => $target->name,
            },
            'health' => $target->currentHealth . '/' . $this->battle->maxHealth($target),
            'side' => $target->team === $actor->team ? 'ally' : 'enemy',
        ], $targets);
    }

    /**
     * The target sent when there is nothing to pick: the only enemy for hostile abilities,
     * else none (abilities used on allies then apply to their user).
     */
    public function defaultTarget(CombatantState $actor, string $abilityKey): ?int
    {
        $targets = $this->chosenTargets($actor, $abilityKey);

        return count($targets) === 1 && $targets[0]->id !== $actor->id ? $targets[0]->id : null;
    }

    /**
     * Valid targets for abilities where the player picks one; none for abilities that choose
     * their own targets (self, everyone, random).
     *
     * @return CombatantState[]
     */
    protected function chosenTargets(CombatantState $actor, string $abilityKey): array
    {
        if (!$this->battle->registry->ability($abilityKey)->targetRule()->needsChosenTarget()) {
            return [];
        }

        return $this->targetsFor($actor, $abilityKey);
    }

    /**
     * A hero's class icon, a helmet for templated allies, else a generic one for enemies.
     */
    public function icon(CombatantState $combatant): string
    {
        $class = $this->model($combatant)?->hero?->class;

        if ($class !== null) {
            return app(HeroHelper::class)->getClassIcon($class);
        }

        return $this->isAlly($combatant) ? 'ra-helmet' : 'ra-monster-skull';
    }

    public function abilityIcon(string $key): string
    {
        return self::ABILITY_ICONS[$key] ?? 'ra-burst-blob';
    }

    /**
     * The tells logged at the end of the last resolved turn: what the enemy will do next, in
     * the log's own words, for the orders panel.
     *
     * @return string[]
     */
    public function tells(): array
    {
        if ($this->model->finished) {
            return [];
        }

        $lastTurn = $this->model->turns->firstWhere('turn', $this->model->current_turn - 1);

        return collect($lastTurn?->events ?? [])
            ->filter(fn (array $event) => $event['type'] === EventType::Message->value && ($event['kind'] ?? null) === MessageKind::Telegraph->value)
            ->pluck('text')
            ->values()
            ->all();
    }

    /**
     * Summaries of every resolved turn, newest first, with the viewer's side as allies.
     * Turn 0 holds setup events and is not shown.
     *
     * @return array<int, array{turn: int, phases: array, gist: array}>
     */
    public function turns(): array
    {
        $allyTeam = $this->viewer?->team ?? 1;
        $combatants = [];
        foreach ($this->battle->combatants() as $combatant) {
            $combatants[$combatant->id] = [
                'name' => $combatant->name,
                'side' => $combatant->team === $allyTeam ? 'ally' : 'enemy',
                'human' => $combatant->isPiloted(),
            ];
        }

        $summary = new TurnSummary($this->battle->registry, $combatants);

        return $this->model->turns
            ->where('turn', '>', 0)
            ->sortByDesc('turn')
            ->map(fn ($row) => $summary->build($row->turn, $row->events))
            ->values()
            ->all();
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

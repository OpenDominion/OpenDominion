<?php

namespace OpenDominion\HeroCombat\Presentation;

use Illuminate\Support\Str;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Events\EventType;
use OpenDominion\HeroCombat\Engine\Events\MessageKind;
use OpenDominion\HeroCombat\Engine\Intent;
use OpenDominion\HeroCombat\Registry\CombatRegistry;

/**
 * Turns one turn's stored events into a phase-by-phase summary: what everyone chose, statuses,
 * damage with its maths, healing, defeats and the result. Actions resolve one at a time in the
 * engine; the phases only group what happened, keeping the order within each phase.
 *
 * A line is a list of segments so views can colour names and numbers without parsing text:
 * text, name (with a side: ally or enemy), damage, heal, math, effect and tag.
 */
class TurnSummary
{
    public const PHASES = [
        'actions' => 'Actions',
        'status' => 'Status',
        'damage' => 'Damage',
        'healing' => 'Healing',
        'defeats' => 'Defeats',
        'outcome' => 'Result',
    ];

    /** Abilities whose hits read as "X attacks Y". */
    protected const BASIC_ATTACKS = ['attack'];

    /** Abilities whose heals read as "X recovers" or "X heals Y". */
    protected const BASIC_HEALS = ['recover'];

    /** @var array<string, int> name => combatant id, longest names first */
    protected array $namesByLength;

    /**
     * @param array<int, array{name: string, side: string, human: bool}> $combatants
     */
    public function __construct(protected CombatRegistry $registry, protected array $combatants)
    {
        $names = [];
        foreach ($combatants as $id => $combatant) {
            $names[$combatant['name']] ??= $id;
        }
        uksort($names, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $this->namesByLength = $names;
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @return array{turn: int, phases: array<int, array{key: string, label: string, lines: array}>, gist: array}
     */
    public function build(int $turn, array $events): array
    {
        $lines = array_fill_keys(array_keys(self::PHASES), []);
        $selected = [];
        $debuffed = [];

        foreach ($events as $event) {
            match ($event['type']) {
                EventType::Selected->value => $lines['actions'][] = $this->selectedLine($event),
                EventType::Damage->value => $lines['damage'][] = $this->damageLine($event),
                EventType::Heal->value => $lines['healing'][] = $this->healLine($event),
                EventType::Died->value => $lines['defeats'][] = $this->line([$this->name($event['target']), $this->text(' is defeated')]),
                EventType::Revived->value => $lines['defeats'][] = $this->line([$this->name($event['target']), $this->text(" returns with {$event['health']} health")]),
                EventType::Summoned->value => $lines['status'][] = $this->summonedLine($event),
                EventType::Reward->value => $lines['outcome'][] = $this->line([$this->name($event['target']), $this->text(" earns {$event['experience']} experience")]),
                EventType::Message->value => $this->addMessage($lines, $event),
                EventType::EffectApplied->value => $this->addDebuff($lines, $debuffed, $event),
                default => null,
            };

            if ($event['type'] === EventType::Selected->value) {
                $selected[] = $event;
            }
        }

        foreach ($this->wastedLines($selected, $events) as $line) {
            $lines['damage'][] = $line;
        }

        $phases = [];
        foreach (self::PHASES as $key => $label) {
            if ($lines[$key] !== []) {
                $phases[] = ['key' => $key, 'label' => $label, 'lines' => $lines[$key]];
            }
        }

        return ['turn' => $turn, 'phases' => $phases, 'gist' => $this->gist($events)];
    }

    /**
     * @param array<string, mixed> $event
     */
    protected function selectedLine(array $event): array
    {
        $segments = [$this->name($event['actor']), $this->text(': ' . $this->abilityName($event['ability']))];

        if ($event['target'] !== null && $event['target'] !== $event['actor']) {
            $segments[] = $this->text(' → ');
            $segments[] = $this->name($event['target']);
        }

        if (($this->combatants[$event['actor']]['human'] ?? false) && $event['source'] === Intent::SOURCE_FORCED) {
            $segments[] = $this->tag('forced');
        }

        return $this->line($segments);
    }

    /**
     * @param array<string, mixed> $event
     */
    protected function damageLine(array $event): array
    {
        $source = $event['source'];
        $target = $event['target'];
        $aimedAt = $event['redirected_from'] ?? null;
        $ability = $event['ability'];

        if ($event['counter']) {
            $segments = [$this->name($source), $this->text(' counters '), $this->name($target)];
        } elseif ($source === null) {
            $segments = [$this->effect($this->abilityName($ability ?? 'damage')), $this->text(' hits '), $this->name($target)];
        } elseif ($ability === null || in_array($ability, self::BASIC_ATTACKS, true)) {
            $segments = [$this->name($source), $this->text(' attacks '), $this->name($aimedAt ?? $target)];
        } else {
            $segments = [$this->name($source), $this->text("'s "), $this->effect($this->abilityName($ability)), $this->text(' hits '), $this->name($aimedAt ?? $target)];
        }

        if ($aimedAt !== null && !$event['counter']) {
            $segments[] = $this->text(', ');
            $segments[] = $this->name($target);
            $segments[] = $this->text(' takes it');
        }

        $segments[] = $this->text(': ');
        array_push($segments, ...$this->damageMath($event));

        return $this->line($segments);
    }

    /**
     * "50 (40 +10 focus) − 40 defense (defending) = 10", with evasion and shields after the raw damage.
     *
     * @param array<string, mixed> $event
     */
    protected function damageMath(array $event): array
    {
        $multiplier = (float) ($event['multiplier'] ?? 1);
        $hits = (int) ($event['hits'] ?? 1);
        $scaling = ($multiplier != 1 ? ' ×' . $this->factor($multiplier) : '') . ($hits > 1 ? " ×{$hits} hits" : '');

        if (!isset($event['attack'])) {
            $segments = [];
        } elseif ($event['flat'] !== null) {
            $segments = [$this->text((string) $event['flat']), $this->math(' (ignores defense)' . $scaling)];
        } else {
            $named = $event['bonuses'] ?? [];
            $parts = array_filter([
                'focus' => $event['focus'],
                'counter' => $event['counter_bonus'],
                ...$named,
                'bonus' => $event['bonus'] - array_sum($named),
            ]);
            $powerMultiplier = (float) ($event['power_multiplier'] ?? 1);
            $power = (int) round(($event['attack'] + array_sum($parts)) * $powerMultiplier);
            $segments = [$this->text(($scaling !== '' ? '(' : '') . $power)];
            if ($parts !== [] || $powerMultiplier != 1) {
                $breakdown = $event['attack'];
                foreach ($parts as $label => $value) {
                    $breakdown .= sprintf(' %+d %s', $value, $label);
                }
                if ($powerMultiplier != 1) {
                    $breakdown = ($parts !== [] ? "({$breakdown})" : $breakdown) . ' ×' . $this->factor($powerMultiplier);
                }
                $segments[] = $this->math(" ({$breakdown})");
            }
            $segments[] = $this->text(" − {$event['defense']} defense");
            if ($event['defending']) {
                $segments[] = $this->math(' (defending)');
            }
            if ($scaling !== '') {
                $segments[] = $this->text(')');
                $segments[] = $this->math($scaling);
            }
        }

        $steps = [];
        if ($event['evaded']) {
            $steps[] = 'evaded, halved';
        }
        foreach ($event['adjustments'] ?? [] as $adjustment) {
            $steps[] = ($adjustment['amount'] >= 0 ? '+' : '−') . abs($adjustment['amount']) . ' ' . $adjustment['label'];
        }
        if ($event['absorbed'] > 0) {
            $steps[] = "−{$event['absorbed']} shield";
        }

        if ($segments === []) {
            $segments[] = $this->damage($event['amount']);
        } elseif ($steps === []) {
            $segments[] = $this->text(' = ');
            $segments[] = $this->damage($event['amount']);
        } else {
            $segments[] = $this->text(" = {$event['raw']}");
            $segments[] = $this->math(', ' . implode(', ', $steps));
            $segments[] = $this->text(' → ');
            $segments[] = $this->damage($event['amount']);
        }

        return $segments;
    }

    /**
     * A multiplier without trailing zeros, e.g. 0.85 or 0.7.
     */
    protected function factor(float $multiplier): string
    {
        return rtrim(rtrim(number_format($multiplier, 2), '0'), '.');
    }

    /**
     * @param array<string, mixed> $event
     */
    protected function healLine(array $event): array
    {
        $source = $event['source'];
        $target = $event['target'];
        $via = $event['via'] ?? null;

        if ($source === null || $source === $target) {
            $segments = [$this->name($target), $this->text(' recovers '), $this->heal($event['amount'])];
        } else {
            $segments = [$this->name($source), $this->text(' heals '), $this->name($target), $this->text(' '), $this->heal($event['amount'])];
        }

        if ($via !== null && !in_array($via, self::BASIC_HEALS, true)) {
            $segments[] = $this->math(' (' . $this->abilityName($via) . ')');
        }

        return $this->line($segments, $event['amount'] === 0);
    }

    /**
     * @param array<string, mixed> $event
     */
    protected function summonedLine(array $event): array
    {
        if ($event['source'] === null) {
            return $this->line([$this->name($event['target']), $this->text(' appears')]);
        }

        return $this->line([$this->name($event['source']), $this->text(' summons '), $this->name($event['target'])]);
    }

    /**
     * A status line for a debuff landing on a combatant, e.g. "Rival is Exposed" or "Foe is
     * Marked by Hero". Each debuff is listed once per combatant per turn.
     *
     * @param array<string, array> $lines
     * @param array<string, bool> $debuffed
     * @param array<string, mixed> $event
     */
    protected function addDebuff(array &$lines, array &$debuffed, array $event): void
    {
        $owner = $event['owner'] ?? null;
        if ($owner === null || !$this->registry->hasEffect($event['effect'])) {
            return;
        }

        $effect = $this->registry->effect($event['effect']);
        $seen = "{$owner}:{$event['effect']}";
        if ($effect->kind() !== EffectKind::Debuff || !$effect->visible() || isset($debuffed[$seen])) {
            return;
        }
        $debuffed[$seen] = true;

        $segments = [$this->name($owner), $this->text(' is '), $this->effect($effect->name())];
        $source = $event['source'] ?? null;
        if ($source !== null && $source !== $owner) {
            $segments[] = $this->text(' by ');
            $segments[] = $this->name($source);
        }

        $lines['status'][] = $this->line($segments);
    }

    /**
     * @param array<string, array> $lines
     * @param array<string, mixed> $event
     */
    protected function addMessage(array &$lines, array $event): void
    {
        $kind = MessageKind::tryFrom($event['kind'] ?? '') ?? MessageKind::Narration;

        match ($kind) {
            MessageKind::Detail => null,
            MessageKind::Outcome => $lines['outcome'][] = $this->line($this->tokenize($event['text'])),
            MessageKind::Narration => $lines['status'][] = $this->line($this->tokenize($event['text'])),
        };
    }

    /**
     * Greyed lines for stances nobody tested: a Defend no one attacked, a Counter never triggered.
     *
     * @param array<int, array<string, mixed>> $selected
     * @param array<int, array<string, mixed>> $events
     * @return array<int, array>
     */
    protected function wastedLines(array $selected, array $events): array
    {
        $hits = array_values(array_filter($events, fn (array $event) => $event['type'] === EventType::Damage->value && $event['source'] !== null));
        $lines = [];

        foreach ($selected as $choice) {
            $actor = $choice['actor'];

            if ($choice['ability'] === 'defend') {
                $ward = $choice['target'] !== null && $choice['target'] !== $actor ? $choice['target'] : null;
                $tested = $ward !== null
                    ? $this->any($hits, fn ($hit) => $hit['target'] === $actor && ($hit['redirected_from'] ?? null) === $ward)
                    : $this->any($hits, fn ($hit) => $hit['target'] === $actor && !$hit['counter']);

                if (!$tested) {
                    $lines[] = $ward !== null
                        ? $this->line([$this->text('Nobody attacked '), $this->name($ward), $this->text(', so '), $this->name($actor), $this->text("'s guard did nothing")], true)
                        : $this->line([$this->name($actor), $this->text(" wasn't attacked, so Defend did nothing")], true);
                }
            }

            if ($choice['ability'] === 'counter' && !$this->any($hits, fn ($hit) => $hit['counter'] && $hit['source'] === $actor)) {
                $lines[] = $this->line([$this->name($actor), $this->text("'s Counter wasn't triggered")], true);
            }
        }

        return $lines;
    }

    /**
     * One-line recap for a collapsed turn: health lost per combatant, then defeats.
     *
     * @param array<int, array<string, mixed>> $events
     */
    protected function gist(array $events): array
    {
        $lost = [];
        $defeated = [];
        foreach ($events as $event) {
            if ($event['type'] === EventType::Damage->value && $event['amount'] > 0) {
                $lost[$event['target']] = ($lost[$event['target']] ?? 0) + $event['amount'];
            } elseif ($event['type'] === EventType::Died->value) {
                $defeated[] = $event['target'];
            }
        }

        $segments = [];
        foreach ($lost as $id => $amount) {
            if ($segments !== []) {
                $segments[] = $this->text(' · ');
            }
            $segments[] = $this->name($id);
            $segments[] = $this->text(' ');
            $segments[] = $this->damage("−{$amount}");
        }
        foreach ($defeated as $id) {
            if ($segments !== []) {
                $segments[] = $this->text(' · ');
            }
            $segments[] = $this->name($id);
            $segments[] = $this->text(' defeated');
        }

        return $segments === [] ? [$this->text('No damage')] : $segments;
    }

    /**
     * Splits narration into text and coloured names.
     */
    protected function tokenize(string $text): array
    {
        if ($this->namesByLength === []) {
            return [$this->text($text)];
        }

        $pattern = '/(' . implode('|', array_map(fn ($name) => preg_quote($name, '/'), array_keys($this->namesByLength))) . ')/u';
        $segments = [];
        foreach (preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part) {
            $segments[] = isset($this->namesByLength[$part]) ? $this->name($this->namesByLength[$part]) : $this->text($part);
        }

        return $segments;
    }

    /**
     * Escaped HTML for a line's segments.
     *
     * @param array<int, array{type: string, text: string, side?: string}> $segments
     */
    public static function html(array $segments): string
    {
        $html = '';
        foreach ($segments as $segment) {
            $text = e($segment['text']);
            $html .= match ($segment['type']) {
                'name' => '<span class="turn-name-' . e($segment['side']) . "\">{$text}</span>",
                'damage' => "<strong class=\"turn-damage\">{$text}</strong>",
                'heal' => "<strong class=\"text-green\">{$text}</strong>",
                'math' => "<span class=\"turn-math\">{$text}</span>",
                'effect' => "<span class=\"turn-effect\">{$text}</span>",
                'tag' => "<span class=\"turn-tag\">{$text}</span>",
                default => $text,
            };
        }

        return $html;
    }

    protected function abilityName(string $key): string
    {
        if ($this->registry->hasAbility($key)) {
            return $this->registry->ability($key)->name();
        }
        if ($this->registry->hasEffect($key)) {
            return $this->registry->effect($key)->name();
        }

        return Str::headline($key);
    }

    protected function any(array $items, callable $test): bool
    {
        foreach ($items as $item) {
            if ($test($item)) {
                return true;
            }
        }

        return false;
    }

    protected function line(array $segments, bool $quiet = false): array
    {
        return ['segments' => $segments, 'quiet' => $quiet];
    }

    protected function name(?int $id): array
    {
        $combatant = $this->combatants[$id] ?? null;

        return ['type' => 'name', 'text' => $combatant['name'] ?? 'Someone', 'side' => $combatant['side'] ?? 'enemy'];
    }

    protected function text(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }

    protected function math(string $text): array
    {
        return ['type' => 'math', 'text' => $text];
    }

    protected function effect(string $text): array
    {
        return ['type' => 'effect', 'text' => $text];
    }

    protected function tag(string $text): array
    {
        return ['type' => 'tag', 'text' => $text];
    }

    protected function damage(int|string $amount): array
    {
        return ['type' => 'damage', 'text' => (string) $amount];
    }

    protected function heal(int $amount): array
    {
        return ['type' => 'heal', 'text' => "+{$amount}"];
    }
}

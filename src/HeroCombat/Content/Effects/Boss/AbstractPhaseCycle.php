<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * Moves its holder through numbered phases on a turn schedule. Each phase applies effects
 * to the holder and an aura to the holder's allies; both are removed when the phase ends
 * or the holder dies.
 *
 * Instance data: phase (int) — the phase currently applied.
 */
abstract class AbstractPhaseCycle extends AbstractPassive
{
    /**
     * @return array<int, array{name: string, self: string[], allies: string[], message: string}>
     */
    abstract protected function phases(): array;

    abstract protected function turnsPerPhase(): int;

    /** Return to phase 1 after the last phase, rather than staying there. */
    protected function cycles(): bool
    {
        return true;
    }

    public function currentPhase(int $turn): int
    {
        $count = count($this->phases());
        $elapsed = (int) floor(($turn - 1) / $this->turnsPerPhase());

        return $this->cycles() ? ($elapsed % $count) + 1 : min($count, $elapsed + 1);
    }

    public function description(EffectInstance $instance): string
    {
        $phase = $this->phases()[$instance->data['phase'] ?? 0]['name'] ?? null;

        return $this->name() . ($phase !== null ? ": {$phase}." : '.');
    }

    public function onTurnEnd(EffectInstance $instance, Battle $battle): void
    {
        $owner = $this->owner($instance, $battle);
        if ($owner === null || !$owner->isAlive()) {
            return;
        }

        $phase = $this->currentPhase($battle->turn());
        if (($instance->data['phase'] ?? null) === $phase) {
            return;
        }

        $this->clearPhase($owner, $battle);
        $instance->data['phase'] = $phase;

        $definition = $this->phases()[$phase];
        foreach ($definition['self'] as $key) {
            $battle->effects->apply($owner, $key, null, 1, ['phase_of' => $owner->id], $owner);
        }
        foreach ($definition['allies'] as $key) {
            $battle->effects->applyToTeam($owner->team, $key, null, 1, ['phase_of' => $owner->id, 'exclude' => [$owner->id]], $owner);
        }

        $battle->say($owner, str_replace('{actor}', $owner->name, $definition['message']));
    }

    public function onDeath(EffectInstance $instance, CombatantState $dead, Battle $battle): void
    {
        $this->clearPhase($dead, $battle);
    }

    protected function clearPhase(CombatantState $owner, Battle $battle): void
    {
        foreach ($battle->effects->all() as $existing) {
            if (($existing->data['phase_of'] ?? null) === $owner->id) {
                $battle->effects->remove($existing);
            }
        }
    }
}

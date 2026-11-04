<?php

namespace OpenDominion\HeroCombat\Engine\Damage;

use OpenDominion\HeroCombat\Engine\CombatantState;

/**
 * Mutable state of a hit while it moves through the damage pipeline. Effect hooks
 * read and adjust this.
 */
final class DamageContext
{
    public CombatantState $target;

    /** Damage after defense, before evasion. */
    public int $raw = 0;

    /** Damage that will be (or was) applied. */
    public int $amount = 0;

    public bool $evaded = false;

    public float $evadeMultiplier = 0.5;

    public bool $attackerFocused = false;

    public int $absorbed = 0;

    public bool $savedFromLethal = false;

    /**
     * Changes effects made to the amount after evasion and before shields, in order, for the
     * turn summary.
     *
     * @var array<int, array{label: string, amount: int}>
     */
    public array $adjustments = [];

    /**
     * How the raw damage was reached, for the turn summary.
     *
     * @var array{flat: int|null, attack: int, focus: int, counter_bonus: int, bonus: int, bonuses: array<string, int>, defense: int, defending: bool, multiplier: float, power_multiplier: float, hits: int}|array{}
     */
    public array $breakdown = [];

    public function __construct(public DamageRequest $request)
    {
        $this->target = $request->target;
    }

    public function attacker(): ?CombatantState
    {
        return $this->request->attacker;
    }

    /**
     * Changes the amount and records why, so the turn summary can show the step.
     */
    public function adjust(string $label, int $amount): void
    {
        $this->amount += $amount;
        if ($amount !== 0) {
            $this->adjustments[] = ['label' => $label, 'amount' => $amount];
        }
    }
}

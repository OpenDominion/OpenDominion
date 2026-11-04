<?php

namespace OpenDominion\HeroCombat\Engine\Damage;

use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\HeroCombat\Engine\Events\BattleEvent;
use OpenDominion\HeroCombat\Engine\Events\EventType;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

/**
 * The single place where a hit becomes health loss:
 * outgoing hooks → redirect → attack vs defense → evasion → incoming hooks (shields)
 * → into the turn ledger → after hooks → counter-attack. Outside a turn's actions, such as
 * effects ticking at the end of a turn, the hit applies at once with its own lethal save.
 */
final class DamageResolver
{
    public const EVADE_MULTIPLIER = 0.5;

    public function __construct(private Battle $battle)
    {
    }

    public function resolve(DamageRequest $request): DamageResult
    {
        $context = new DamageContext($request);
        $attacker = $request->attacker;

        if ($attacker !== null) {
            $context->attackerFocused = $this->battle->hasTag($attacker, CombatTag::Focused);
            $this->battle->dispatcher->run(Hook::BeforeDamageDealt, [$attacker], $context, $this->battle);
        }

        $this->battle->dispatcher->run(Hook::RedirectDamage, [$context->target], $context, $this->battle);
        $target = $context->target;
        $this->battle->dispatcher->run(Hook::BeforeDamageCalculated, [$target], $context, $this->battle);

        $context->raw = $this->rawDamage($context);
        $context->amount = $context->raw;

        if ($request->canEvade && $context->raw > 0 && $this->rollEvade($target)) {
            $context->evaded = true;
            $context->evadeMultiplier = self::EVADE_MULTIPLIER;
            $this->battle->dispatcher->run(Hook::Evaded, [$target], $context, $this->battle);
            $context->amount = (int) round($context->raw * $context->evadeMultiplier);
        }

        $this->battle->dispatcher->run(Hook::BeforeDamageTaken, [$target], $context, $this->battle);
        $context->amount = max(0, $context->amount);

        if ($this->battle->ledger !== null) {
            $this->battle->ledger->addDamage($target, $context->amount, $request->bypassLethalSave);
        } else {
            $this->applyNow($context);
        }
        $this->record($context);

        if ($context->amount > 0) {
            $this->battle->effects->notifyDamageTaken($target);
            $this->battle->dispatcher->run(Hook::AfterDamageTaken, [$target], $context, $this->battle);
            if ($attacker !== null) {
                $this->battle->dispatcher->run(Hook::AfterDamageDealt, [$attacker], $context, $this->battle);
            }
        }

        $counter = null;
        $counterer = $this->counterer($context);
        if ($counterer !== null) {
            $counter = $this->counterAttack($counterer, $attacker, $request->hits);
        }

        return new DamageResult(
            target: $target,
            amount: $context->amount,
            raw: $context->raw,
            evaded: $context->evaded,
            absorbed: $context->absorbed,
            savedFromLethal: $context->savedFromLethal,
            counter: $counter,
            counterer: $counterer,
        );
    }

    /**
     * Who strikes back at this hit: the one it struck, or the one it was aimed at if a guard
     * stepped in front of them.
     */
    private function counterer(DamageContext $context): ?CombatantState
    {
        $request = $context->request;
        if ($request->attacker === null || !$request->canBeCountered || $request->isCounter) {
            return null;
        }

        foreach ([$context->target, $request->target] as $candidate) {
            if ($candidate !== $request->attacker && $this->battle->hasTag($candidate, CombatTag::Countering)) {
                return $candidate;
            }
        }

        return null;
    }

    private function applyNow(DamageContext $context): void
    {
        $target = $context->target;

        if ($target->isAlive() && $context->amount >= $target->currentHealth && !$context->request->bypassLethalSave) {
            $context->savedFromLethal = $this->battle->dispatcher->runUntilTrue(
                Hook::LethalDamage,
                [$target],
                $context,
                $this->battle,
            );
        }

        $target->currentHealth = max(0, $target->currentHealth - $context->amount);
    }

    /**
     * A retaliation: attack + counter versus defense, no evasion, not itself counterable,
     * and not absorbed by shields.
     */
    public function counterAttack(CombatantState $counterer, CombatantState $victim, int $hits = 1): DamageResult
    {
        return $this->resolve(new DamageRequest(
            attacker: $counterer,
            target: $victim,
            hits: $hits,
            canEvade: false,
            canBeCountered: false,
            ignoreShield: true,
            isCounter: true,
        ));
    }

    /**
     * Damage a hit would deal after defense and before evasion.
     */
    public function rawDamage(DamageContext $context): int
    {
        $request = $context->request;
        $target = $context->target;

        $breakdown = [
            'flat' => $request->flatDamage,
            'attack' => 0,
            'focus' => 0,
            'counter_bonus' => 0,
            'bonus' => $request->bonusDamage,
            'bonuses' => $request->bonusParts,
            'defense' => 0,
            'defending' => false,
            'multiplier' => $request->multiplier,
            'power_multiplier' => $request->powerMultiplier,
            'hits' => $request->hits,
        ];

        if ($request->flatDamage !== null) {
            $context->breakdown = $breakdown;

            return (int) round($request->flatDamage * $request->multiplier * $request->hits);
        }

        $attacker = $request->attacker;
        if ($attacker !== null) {
            $breakdown['attack'] = $this->battle->stat($attacker, Stat::Attack);
            if ($request->isCounter) {
                $breakdown['counter_bonus'] = $this->battle->stat($attacker, Stat::Counter);
            } elseif ($context->attackerFocused) {
                $stacks = max(1, $this->battle->effects->stacks($attacker, 'focused'));
                $breakdown['focus'] = $this->battle->stat($attacker, Stat::Focus) * $stacks;
            }
        }

        if (!$request->ignoreDefense) {
            $breakdown['defense'] = $this->battle->stat($target, Stat::Defense);
            if ($this->battle->hasTag($target, CombatTag::Defending)) {
                $breakdown['defending'] = true;
                $breakdown['defense'] += $request->defendModifier;
            }
        }

        $context->breakdown = $breakdown;
        $power = (int) round(($breakdown['attack'] + $breakdown['counter_bonus'] + $breakdown['focus'] + $breakdown['bonus']) * $request->powerMultiplier);

        return (int) round(max(0, $power - $breakdown['defense']) * $request->multiplier * $request->hits);
    }

    private function rollEvade(CombatantState $target): bool
    {
        $evasion = $this->battle->stat($target, Stat::Evasion);

        if ($this->battle->evasionRoll !== null) {
            return ($this->battle->evasionRoll)($target, $evasion);
        }

        return $this->battle->random->int(0, 100) < $evasion;
    }

    private function record(DamageContext $context): void
    {
        $request = $context->request;
        $entry = $this->battle->log->current();

        if ($entry !== null && $request->attacker !== null) {
            if ($request->attacker->id === $entry->actorId && $context->target->id !== $entry->actorId) {
                $entry->damage += $context->amount;
            }
            if ($context->target->id === $entry->actorId) {
                $entry->health -= $context->amount;
            }
        }

        $this->battle->event(new BattleEvent(EventType::Damage, [
            'source' => $request->attacker?->id,
            'target' => $context->target->id,
            'ability' => $request->abilityKey,
            'amount' => $context->amount,
            'raw' => $context->raw,
            'evaded' => $context->evaded,
            'absorbed' => $context->absorbed,
            'counter' => $request->isCounter,
            'redirected_from' => $context->target !== $request->target ? $request->target->id : null,
            'adjustments' => $context->adjustments,
        ] + $context->breakdown));
    }
}

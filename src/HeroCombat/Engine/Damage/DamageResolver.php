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
 * → lethal saves → apply → after hooks → counter-attack.
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

        if ($target->isAlive() && $context->amount >= $target->currentHealth && !$request->bypassLethalSave) {
            $context->savedFromLethal = $this->battle->dispatcher->runUntilTrue(
                Hook::LethalDamage,
                [$target],
                $context,
                $this->battle,
            );
        }

        $target->currentHealth = max(0, $target->currentHealth - $context->amount);
        $this->record($context);

        if ($context->amount > 0) {
            $this->battle->effects->notifyDamageTaken($target);
            $this->battle->dispatcher->run(Hook::AfterDamageTaken, [$target], $context, $this->battle);
            if ($attacker !== null) {
                $this->battle->dispatcher->run(Hook::AfterDamageDealt, [$attacker], $context, $this->battle);
            }
        }

        $counter = null;
        if ($attacker !== null && $request->canBeCountered && !$request->isCounter && $attacker !== $target
            && $this->battle->hasTag($target, CombatTag::Countering)) {
            $counter = $this->counterAttack($target, $attacker, $request->hits);
        }

        return new DamageResult(
            target: $target,
            amount: $context->amount,
            raw: $context->raw,
            evaded: $context->evaded,
            absorbed: $context->absorbed,
            savedFromLethal: $context->savedFromLethal,
            counter: $counter,
        );
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

        if ($request->flatDamage !== null) {
            return (int) round($request->flatDamage * $request->multiplier * $request->hits);
        }

        $attacker = $request->attacker;
        $power = $attacker !== null ? $this->battle->stat($attacker, Stat::Attack) : 0;

        if ($attacker !== null && $request->isCounter) {
            $power += $this->battle->stat($attacker, Stat::Counter);
        } elseif ($attacker !== null && $context->attackerFocused) {
            $stacks = max(1, $this->battle->effects->stacks($attacker, 'focused'));
            $power += $this->battle->stat($attacker, Stat::Focus) * $stacks;
        }

        $power += $request->bonusDamage;

        $defense = 0;
        if (!$request->ignoreDefense) {
            $defense = $this->battle->stat($target, Stat::Defense);
            if ($this->battle->hasTag($target, CombatTag::Defending)) {
                $defense += $request->defendModifier;
            }
        }

        return (int) round(max(0, $power - $defense) * $request->multiplier * $request->hits);
    }

    private function rollEvade(CombatantState $target): bool
    {
        return $this->battle->random->int(0, 100) < $this->battle->stat($target, Stat::Evasion);
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
        ]));
    }
}

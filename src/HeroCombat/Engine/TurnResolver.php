<?php

namespace OpenDominion\HeroCombat\Engine;

use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Damage\DamageRequest;
use OpenDominion\HeroCombat\Engine\Effects\EffectScope;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\HeroCombat\Engine\Events\BattleEvent;
use OpenDominion\HeroCombat\Engine\Events\EventType;
use OpenDominion\HeroCombat\Engine\Events\MessageKind;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

/**
 * Resolves one simultaneous turn:
 * turn start → intents → declare (stances, shields) → damage step → healing step → settle
 * health and deaths → spend, then gain → turn end hooks → durations → encounter script
 * → victory check. Damage and healing go into the turn ledger and land together, and changes
 * to stats wait until the turn has settled, so the order actions resolve in (combatant id
 * within each step) never decides the outcome.
 */
final class TurnResolver
{
    public function resolve(Battle $battle): void
    {
        $state = $battle->state;
        $validator = new ActionValidator($battle);
        $decider = new IntentDecider($battle, $validator);

        $state->intents = [];
        $battle->dispatcher->runEverywhere(Hook::TurnStart, $battle);

        foreach ($battle->living() as $combatant) {
            $state->intents[$combatant->id] = $decider->decide($combatant);
        }

        $contexts = [];
        foreach ($state->intents as $actorId => $intent) {
            $context = new ActionContext(
                $battle,
                $battle->combatant($actorId),
                $battle->registry->ability($intent->abilityKey),
                $intent,
            );
            $context->ability->onDeclare($context);
            $battle->dispatcher->runOwn(Hook::AbilityDeclared, $context->actor, $context, $battle);
            $contexts[] = $context;
        }

        usort($contexts, fn (ActionContext $a, ActionContext $b) => $a->actor->id <=> $b->actor->id);
        $heals = array_filter($contexts, fn (ActionContext $context) => $this->heals($context));
        $others = array_filter($contexts, fn (ActionContext $context) => !$this->heals($context));

        $battle->ledger = new TurnLedger();
        foreach ($others as $context) {
            $this->resolveAction($battle, $validator, $context);
        }

        foreach ($battle->ledger->startHealing() as $heal) {
            $heal();
        }
        foreach ($heals as $context) {
            $this->resolveAction($battle, $validator, $context);
        }

        $this->settle($battle);

        $battle->dispatcher->runEverywhere(Hook::TurnEnd, $battle);
        $battle->effects->tickTurnEnd();
        $battle->encounter?->onTurnEnd($battle);
        $this->processDeaths($battle);

        $result = $battle->victoryCondition()->evaluate($battle);
        if ($result !== null) {
            $state->finished = true;
            $state->winningTeam = $result->winningTeam;
            $this->announceVictory($battle);
            return;
        }

        $state->turn++;
    }

    /**
     * Healing actions resolve in their own step, once every action's damage has landed, so
     * what they restore counts against the turn's real damage.
     */
    private function heals(ActionContext $context): bool
    {
        return in_array(CombatTag::Heal, $context->ability->tags(), true);
    }

    /**
     * Applies the turn's damage and healing together and processes deaths. Then what the
     * turn's actions spent is taken away before what they earned is given, and only those
     * still standing gain anything.
     */
    private function settle(Battle $battle): void
    {
        $ledger = $battle->ledger;
        $battle->ledger = null;

        foreach ($battle->living() as $combatant) {
            $health = $ledger->projectedHealth($combatant);
            if ($health <= 0 && $ledger->canBeSaved($combatant)) {
                $health = $this->healthAfterLethalSave($battle, $combatant, $health);
            }

            $combatant->currentHealth = max(0, min($health, $battle->maxHealth($combatant)));

            if (!$combatant->isAlive()) {
                foreach ($ledger->defeatCallbacks($combatant) as $callback) {
                    $callback();
                }
            }
        }

        $this->processDeaths($battle);

        foreach ($ledger->spendCallbacks() as $callback) {
            $callback();
        }

        foreach ($ledger->gainCallbacks() as [$recipient, $callback]) {
            if ($recipient->isAlive()) {
                $callback();
            }
        }
    }

    private function healthAfterLethalSave(Battle $battle, CombatantState $combatant, int $health): int
    {
        $damage = new DamageContext(new DamageRequest(null, $combatant));
        $damage->amount = $combatant->currentHealth - $health;

        $saved = $battle->dispatcher->runUntilTrue(Hook::LethalDamage, [$combatant], $damage, $battle);

        return $saved ? $combatant->currentHealth - $damage->amount : $health;
    }

    private function resolveAction(Battle $battle, ActionValidator $validator, ActionContext $context): void
    {
        $actor = $context->actor;
        $ability = $context->ability;

        $context->targets = $battle->targets->resolve($actor, $ability, $context->intent->targetId);
        $battle->log->begin($battle->turn(), $actor->id, $ability->key(), $context->target()?->id);
        $battle->event(new BattleEvent(EventType::Selected, [
            'actor' => $actor->id,
            'ability' => $ability->key(),
            'target' => $context->intent->targetId,
            'source' => $context->intent->source,
        ]));

        if ($context->targets === [] && $ability->targetRule() !== TargetRule::Self && $ability->targetRule()->needsChosenTarget()) {
            $battle->say($actor, "{$actor->name}'s {$ability->name()} finds no target.");
            $battle->event(new BattleEvent(EventType::Fizzled, ['actor' => $actor->id, 'ability' => $ability->key()]));
        } else {
            $ability->resolve($context);
        }

        $validator->recordUse($actor, $ability);
        $actor->lastAction = $ability->recordedAction($context);
        $battle->dispatcher->run(Hook::AbilityUsed, [$actor], $context, $battle);
        $battle->effects->tickOwnerActionEnd($actor);

        $battle->log->end();
    }

    /**
     * Runs death triggers for every newly downed combatant, repeating until no new deaths
     * occur (a death trigger may kill or summon others).
     */
    public function processDeaths(Battle $battle): void
    {
        do {
            $changed = false;
            foreach ($battle->combatants() as $combatant) {
                if ($combatant->isAlive() || $combatant->deathProcessed) {
                    continue;
                }

                $combatant->deathProcessed = true;
                $changed = true;
                $battle->event(new BattleEvent(EventType::Died, ['target' => $combatant->id]));

                $battle->dispatcher->runOwn(Hook::Death, $combatant, $combatant, $battle);

                foreach ($battle->effects->all() as $instance) {
                    if ($instance->scope === EffectScope::Combatant && $instance->ownerId === $combatant->id) {
                        continue;
                    }
                    $battle->registry->effect($instance->key)->onAnyDeath($instance, $combatant, $battle);
                }

                if ($combatant->isAlive()) {
                    $combatant->deathProcessed = false;
                }
            }
        } while ($changed);
    }

    private function announceVictory(Battle $battle): void
    {
        $message = $battle->encounter?->victoryMessage();
        if ($message === null || $battle->state->winningTeam === null) {
            return;
        }

        foreach ($battle->living() as $combatant) {
            if ($combatant->team === $battle->state->winningTeam && $combatant->isHero()) {
                $battle->say($combatant, $message, MessageKind::Outcome);
                return;
            }
        }
    }
}

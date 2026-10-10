<?php

namespace OpenDominion\HeroCombat\Engine;

use OpenDominion\HeroCombat\Engine\Effects\EffectScope;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\HeroCombat\Engine\Events\BattleEvent;
use OpenDominion\HeroCombat\Engine\Events\EventType;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

/**
 * Resolves one simultaneous turn:
 * turn start → intents → declare → order by priority → resolve each → deaths
 * → turn end hooks → durations → encounter script → victory check.
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
            $contexts[] = $context;
        }

        usort($contexts, fn (ActionContext $a, ActionContext $b) => [$b->ability->priority(), $a->actor->id] <=> [$a->ability->priority(), $b->actor->id]);

        foreach ($contexts as $context) {
            $this->resolveAction($battle, $validator, $context);
        }

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
        $actor->lastAction = $ability->key();
        $battle->dispatcher->run(Hook::AbilityUsed, [$actor], $context, $battle);
        $battle->effects->tickOwnerActionEnd($actor);
        $this->processDeaths($battle);

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
            if ($combatant->team === $battle->state->winningTeam && $combatant->isHuman()) {
                $battle->say($combatant, $message);
                return;
            }
        }
    }
}

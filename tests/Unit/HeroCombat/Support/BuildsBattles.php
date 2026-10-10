<?php

namespace OpenDominion\Tests\Unit\HeroCombat\Support;

use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\BattleEngine;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Intent;
use OpenDominion\HeroCombat\Engine\TurnResolver;

trait BuildsBattles
{
    protected function named(Battle $battle, string $name): CombatantState
    {
        foreach ($battle->combatants() as $combatant) {
            if ($combatant->name === $name) {
                return $combatant;
            }
        }

        $this->fail("No combatant named {$name}");
    }

    /**
     * Queues one action for a human combatant.
     */
    protected function queue(Battle $battle, string $name, string $ability, ?string $targetName = null): void
    {
        $actor = $this->named($battle, $name);
        $actor->queue[] = [
            'ability' => $ability,
            'target' => $targetName !== null ? $this->named($battle, $targetName)->id : null,
        ];
    }

    /**
     * Records what a combatant chose this turn and runs its declare step (stances).
     */
    protected function declare(Battle $battle, string $name, string $ability): void
    {
        $actor = $this->named($battle, $name);
        $intent = new Intent($actor->id, $ability);
        $battle->state->intents[$actor->id] = $intent;
        $battle->registry->ability($ability)->onDeclare(new ActionContext($battle, $actor, $battle->registry->ability($ability), $intent));
    }

    /**
     * Resolves one ability directly and returns the text it logged.
     */
    protected function perform(Battle $battle, string $actorName, string $ability, ?string $targetName = null): string
    {
        $actor = $this->named($battle, $actorName);
        $definition = $battle->registry->ability($ability);
        $intent = new Intent($actor->id, $ability, $targetName !== null ? $this->named($battle, $targetName)->id : null);
        $context = new ActionContext($battle, $actor, $definition, $intent);
        $context->targets = $battle->targets->resolve($actor, $definition, $intent->targetId);

        $entry = $battle->log->begin($battle->turn(), $actor->id, $ability, $context->target()?->id);
        $definition->resolve($context);
        (new TurnResolver())->processDeaths($battle);
        $battle->log->end();

        return $entry->description();
    }

    protected function resolveTurn(Battle $battle): void
    {
        (new TurnResolver())->resolve($battle);
    }

    protected function engine(): BattleEngine
    {
        return new BattleEngine();
    }
}

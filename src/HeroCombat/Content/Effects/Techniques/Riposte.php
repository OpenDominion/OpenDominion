<?php

namespace OpenDominion\HeroCombat\Content\Effects\Techniques;

use OpenDominion\HeroCombat\Content\Effects\Marked;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * Technique: a Counter that deals damage Marks the attacker until the end of the next turn.
 */
class Riposte extends AbstractMarkingPassive
{
    public function key(): string
    {
        return 'riposte';
    }

    public function name(): string
    {
        return 'Riposte';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Landing a Counter Marks the attacker: attacks on them deal +' . Marked::BONUS_DAMAGE . ' damage for ' . Marked::TURNS . ' turns.';
    }

    public function rulesNotes(): array
    {
        return [
            'Marked starts the turn after and lasts ' . Marked::TURNS . ' turns. Being Marked again starts the turns over; it doesn\'t stack.',
            'The bonus is added before defense, like Backstab, to attacks from you or your ally.',
            'Counters don\'t get the bonus.',
        ];
    }

    public function afterDamageDealt(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        if ($damage->request->attacker?->id !== $instance->ownerId || !$damage->request->isCounter || !$damage->target->isAlive()) {
            return;
        }

        $this->markAfterActions($damage, $battle);
    }
}

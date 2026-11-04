<?php

namespace OpenDominion\HeroCombat\Content\Effects\Techniques;

use OpenDominion\HeroCombat\Content\Effects\Marked;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * Technique: a Focused attack that hits without being evaded Marks the target until the end
 * of the next turn.
 */
class Spellbrand extends AbstractMarkingPassive
{
    public function key(): string
    {
        return 'spellbrand';
    }

    public function name(): string
    {
        return 'Spellbrand';
    }

    public function description(EffectInstance $instance): string
    {
        return 'A Focused attack that hits Marks the target: attacks on them deal +' . Marked::BONUS_DAMAGE . ' damage for ' . Marked::TURNS . ' turns.';
    }

    public function rulesNotes(): array
    {
        return [
            'Marked starts the turn after and lasts ' . Marked::TURNS . ' turns. Being Marked again starts the turns over; it doesn\'t stack.',
            'The bonus is added before defense, like Backstab, to attacks from you or your ally.',
            'Counters don\'t get the bonus.',
            'An evaded attack doesn\'t Mark.',
        ];
    }

    public function afterDamageDealt(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        $request = $damage->request;
        if ($request->attacker?->id !== $instance->ownerId || $request->isCounter || !$request->hasTag(CombatTag::Attack)
            || !$damage->attackerFocused || $damage->evaded || !$damage->target->isAlive()) {
            return;
        }

        $this->markAfterActions($damage, $battle);
    }
}

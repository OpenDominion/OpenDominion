<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Intent;

/**
 * Forces an attack on the turn after it is applied and doubles that attack's damage.
 * Consumed by any Attack-tagged ability; otherwise expires at the end of that turn.
 */
class ArcaneConduit extends AbstractEffect
{
    public const DAMAGE_MULTIPLIER = 2.0;

    public function key(): string
    {
        return 'arcane_conduit';
    }

    public function name(): string
    {
        return 'Arcane Conduit';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Next attack deals double damage.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Buff;
    }

    public function defaultDuration(): ?int
    {
        return 2;
    }

    public function forcedIntent(EffectInstance $instance, CombatantState $subject, Battle $battle): ?Intent
    {
        if ($subject->id !== $instance->ownerId || $battle->turn() <= $instance->appliedTurn) {
            return null;
        }

        return new Intent($subject->id, 'attack', null, Intent::SOURCE_FORCED);
    }

    public function beforeDamageDealt(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        if ($damage->attacker()?->id !== $instance->ownerId || $damage->request->isCounter || !$damage->request->hasTag(CombatTag::Attack)) {
            return;
        }

        $damage->request->multiplier *= self::DAMAGE_MULTIPLIER;
    }

    public function onAbilityUsed(EffectInstance $instance, ActionContext $context, Battle $battle): void
    {
        if ($context->actor->id === $instance->ownerId && in_array(CombatTag::Attack, $context->ability->tags(), true)) {
            $battle->effects->remove($instance);
        }
    }
}

<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;

/**
 * An ally's Focus: adds their focus value to attacks this turn only. Wasted if the holder
 * doesn't attack.
 */
class Rallied extends AbstractEffect
{
    public function key(): string
    {
        return 'rallied';
    }

    public function name(): string
    {
        return 'Rallied';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Attacks this turn deal +' . ($instance->data['bonus'] ?? 0) . ' damage.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Buff;
    }

    public function defaultDuration(): ?int
    {
        return 1;
    }

    public function beforeDamageDealt(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        $request = $damage->request;
        if ($request->attacker?->id !== $instance->ownerId || $request->isCounter || !$request->hasTag(CombatTag::Attack)) {
            return;
        }

        $request->addBonus('rally', (int) ($instance->data['bonus'] ?? 0));
    }
}

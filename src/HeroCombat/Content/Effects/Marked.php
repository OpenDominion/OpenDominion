<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;

/**
 * Attacks on the holder deal bonus damage, whoever makes them, added before defense like
 * Backstab's. Applied after the turn's actions, so it lasts for the next TURNS turns.
 */
class Marked extends AbstractEffect
{
    public const BONUS_DAMAGE = 10;
    public const TURNS = 2;

    public function key(): string
    {
        return 'marked';
    }

    public function name(): string
    {
        return 'Marked';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Attacks deal +' . self::BONUS_DAMAGE . ' damage.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Debuff;
    }

    public function defaultDuration(): ?int
    {
        return self::TURNS + 1;
    }

    public function beforeDamageCalculated(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        $request = $damage->request;
        if ($request->attacker === null || $request->isCounter || !$request->hasTag(CombatTag::Attack)) {
            return;
        }

        $request->addBonus($this->name(), self::BONUS_DAMAGE);
    }
}

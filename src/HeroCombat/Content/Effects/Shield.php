<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Effects\ExpiryTiming;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\HeroCombat\Engine\Effects\StackingRule;

/**
 * Absorbs incoming damage until its pool (data.pool) is spent.
 */
class Shield extends AbstractEffect
{
    public function key(): string
    {
        return 'shield';
    }

    public function name(): string
    {
        return 'Shield';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Absorbs the next ' . ($instance->data['pool'] ?? 0) . ' damage.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Buff;
    }

    public function stacking(): StackingRule
    {
        return StackingRule::Replace;
    }

    public function expiry(): ExpiryTiming
    {
        return ExpiryTiming::OnConsume;
    }

    public function tags(): array
    {
        return [CombatTag::Shielded];
    }

    public function handlerPriority(Hook $hook): int
    {
        return 10;
    }

    public function beforeDamageTaken(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        if ($damage->request->ignoreShield || $damage->amount <= 0) {
            return;
        }

        $pool = (int) ($instance->data['pool'] ?? 0);
        $absorbed = min($pool, $damage->amount);
        $damage->amount -= $absorbed;
        $damage->absorbed += $absorbed;
        $instance->data['pool'] = $pool - $absorbed;

        if ($instance->data['pool'] <= 0) {
            $battle->effects->remove($instance);
        }
    }
}

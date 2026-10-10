<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

class CrushingBlow extends AbstractPassive
{
    public const BONUS_DAMAGE = 15;

    public function key(): string
    {
        return 'crushing_blow';
    }

    public function name(): string
    {
        return 'Crushing Blow';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Attacks deal ' . self::BONUS_DAMAGE . ' additional damage if the target is not defending.';
    }

    public function beforeDamageDealt(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        if ($damage->request->isCounter || $damage->request->abilityKey !== 'attack') {
            return;
        }

        $damage->request->bonusDamage += self::BONUS_DAMAGE;
        $damage->request->defendModifier += self::BONUS_DAMAGE;
    }
}

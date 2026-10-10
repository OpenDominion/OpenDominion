<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Engine\ActionContext;

/**
 * Heals for the damage dealt; defending blunts it, attacking into it is punished.
 */
class Bloodrend extends AbstractBossMove
{
    public const DEFEND_DAMAGE = 15;
    public const DEFAULT_DAMAGE = 30;
    public const ATTACK_DAMAGE = 50;

    public function key(): string
    {
        return 'bloodrend';
    }

    public function name(): string
    {
        return 'Bloodrend';
    }

    public function resolve(ActionContext $context): void
    {
        $target = $context->target();

        [$messageKey, $damage, $healing] = match (true) {
            $context->chose($target, 'defend') => ['defend', self::DEFEND_DAMAGE, (int) round(self::DEFEND_DAMAGE / 2)],
            $context->chose($target, 'attack') => ['attack', self::ATTACK_DAMAGE, self::ATTACK_DAMAGE],
            default => ['default', self::DEFAULT_DAMAGE, self::DEFAULT_DAMAGE],
        };

        $result = $context->flatDamage($target, $damage);
        $healed = $context->heal($context->actor, $healing);
        $context->say($messageKey, ['damage' => $result->amount + $result->absorbed, 'healing' => $healed]);
    }

    public function messages(): array
    {
        return [
            'defend' => '{actor} tears into {target} for {damage} damage, but their defensive stance blunts it. She heals for {healing} health.',
            'attack' => '{actor} reads {target}\'s wild swing and drives her claws deep for {damage} damage. She feasts, healing {healing} health.',
            'default' => '{actor} tears into {target} for {damage} damage. She heals for {healing} health.',
        ];
    }
}

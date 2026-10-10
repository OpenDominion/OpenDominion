<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Engine\ActionContext;

/**
 * Punishes Counter, rewards Defend, and also cuts down the Admiral's own defenders.
 */
class Broadside extends AbstractBossMove
{
    public const DEFEND_DAMAGE = 15;
    public const DEFAULT_DAMAGE = 30;
    public const COUNTER_DAMAGE = 45;
    public const VOLLEY_DAMAGE = 45;

    public function key(): string
    {
        return 'broadside';
    }

    public function name(): string
    {
        return 'Broadside';
    }

    public function resolve(ActionContext $context): void
    {
        $allies = $context->battle->alliesOf($context->actor, includeSelf: false);
        foreach ($allies as $ally) {
            $context->flatDamage($ally, self::VOLLEY_DAMAGE, ['ignoreShield' => true, 'bypassLethalSave' => true]);
        }

        $target = $context->target();
        [$messageKey, $damage] = match (true) {
            $context->chose($target, 'defend') => ['defend', self::DEFEND_DAMAGE],
            $context->chose($target, 'counter') => ['counter', self::COUNTER_DAMAGE],
            default => ['default', self::DEFAULT_DAMAGE],
        };

        $result = $context->flatDamage($target, $damage);
        $context->say($messageKey, ['damage' => $result->amount + $result->absorbed]);

        if ($allies !== []) {
            $context->say('defenders');
        }
    }

    public function messages(): array
    {
        return [
            'defend' => '{actor}\'s guns roar across the harbor. {target} drops behind the stonework and the shot deals {damage} damage.',
            'counter' => '{actor}\'s guns roar across the harbor. {target} stands poised to riposte a blade that never comes, and takes {damage} damage.',
            'default' => '{actor}\'s guns roar across the harbor, tearing into {target} for {damage} damage.',
            'defenders' => 'The guns do not discriminate. The defenders are caught in the volley.',
        ];
    }
}

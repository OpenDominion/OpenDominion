<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Engine\ActionContext;

/**
 * Baits an attack. Focusing ignores the jeering crew and negates it.
 */
class AdmiralsChallenge extends AbstractBossMove
{
    public const DEFAULT_DAMAGE = 25;
    public const ATTACK_DAMAGE = 45;

    public function key(): string
    {
        return 'admirals_challenge';
    }

    public function name(): string
    {
        return 'The Admiral\'s Challenge';
    }

    public function resolve(ActionContext $context): void
    {
        $target = $context->target();

        if ($context->chose($target, 'focus')) {
            $context->say('focus');
            return;
        }

        [$messageKey, $damage] = $context->chose($target, 'attack')
            ? ['attack', self::ATTACK_DAMAGE]
            : ['default', self::DEFAULT_DAMAGE];

        $result = $context->flatDamage($target, $damage);
        $context->say($messageKey, ['damage' => $result->amount + $result->absorbed]);
    }

    public function messages(): array
    {
        return [
            'focus' => '{target} says nothing, watching the blade instead of the crew. {actor}\'s challenge goes unanswered.',
            'attack' => '{target} takes the bait and lunges. {actor} was waiting for it, opening them up for {damage} damage.',
            'default' => '{actor}\'s cutlass finds its mark for {damage} damage.',
        ];
    }
}

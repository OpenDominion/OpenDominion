<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Engine\ActionContext;

/**
 * Flat frost damage. Attacking interrupts it; defending makes it worse.
 */
class WintersBreath extends AbstractBossMove
{
    public const DEFAULT_DAMAGE = 30;
    public const DEFEND_DAMAGE = 50;

    public function key(): string
    {
        return 'winters_breath';
    }

    public function name(): string
    {
        return 'Winter\'s Breath';
    }

    public function resolve(ActionContext $context): void
    {
        $target = $context->target();

        if ($context->chose($target, 'attack')) {
            $context->say('attack');
            return;
        }

        [$messageKey, $damage] = $context->chose($target, 'defend')
            ? ['defend', self::DEFEND_DAMAGE]
            : ['default', self::DEFAULT_DAMAGE];

        $result = $context->flatDamage($target, $damage);
        $context->say($messageKey, ['damage' => $result->amount + $result->absorbed]);
    }

    public function messages(): array
    {
        return [
            'attack' => '{target} lunges through the gathering storm, interrupting {actor}\'s breath.',
            'defend' => '{actor} exhales a cone of killing frost. {target}\'s defensive stance intensifies the cold, taking {damage} damage.',
            'default' => '{actor} exhales a cone of killing frost, dealing {damage} damage to {target}.',
        ];
    }
}

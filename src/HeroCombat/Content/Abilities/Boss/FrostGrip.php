<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Engine\ActionContext;

/**
 * Freezes the target next turn. Focus breaks it; recovering lets frostbite seep in.
 */
class FrostGrip extends AbstractBossMove
{
    public const RECOVER_FROSTBITE_STACKS = 5;

    public function key(): string
    {
        return 'frost_grip';
    }

    public function name(): string
    {
        return 'Frost Grip';
    }

    public function resolve(ActionContext $context): void
    {
        $target = $context->target();

        if ($context->chose($target, 'focus')) {
            $context->say('focus');
            return;
        }

        $context->applyEffect($target, 'freezing');

        if ($context->chose($target, 'recover')) {
            $context->applyEffect($target, 'frostbitten', null, self::RECOVER_FROSTBITE_STACKS);
            $context->say('recover');
            return;
        }

        $context->say('default');
    }

    public function messages(): array
    {
        return [
            'focus' => '{target}\'s clarity of mind shatters the creeping ice.',
            'recover' => 'The ice climbs unnoticed as {target} tends to their wounds. {target} is frozen, frostbite seeping deep into their bones.',
            'default' => 'Ice locks around {target}\'s legs. {target} is frozen.',
        ];
    }
}

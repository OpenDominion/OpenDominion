<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

/**
 * Chosen when nothing else is usable (e.g. while stunned).
 */
class Pass extends AbstractAbility
{
    public function key(): string
    {
        return 'pass';
    }

    public function name(): string
    {
        return 'Pass';
    }

    public function description(): string
    {
        return 'Does nothing.';
    }

    public function targetRule(): TargetRule
    {
        return TargetRule::Self;
    }

    public function blockedByTags(): array
    {
        return [];
    }

    public function selectable(): bool
    {
        return false;
    }

    public function resolve(ActionContext $context): void
    {
        $context->say('pass');
    }

    public function messages(): array
    {
        return ['pass' => '{actor} is unable to act.'];
    }
}

<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

class Darkness extends AbstractBossMove
{
    public function key(): string
    {
        return 'darkness';
    }

    public function name(): string
    {
        return 'Darkness';
    }

    public function targetRule(): TargetRule
    {
        return TargetRule::Self;
    }

    public function resolve(ActionContext $context): void
    {
        $context->applyEffect($context->actor, 'shrouded');
        $context->say('darkness');
    }

    public function messages(): array
    {
        return ['darkness' => '{actor} increases evasion value by 20.'];
    }
}

<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

/**
 * Ullyaoth spends its turn seizing its champion. The meal itself happens at the end of the
 * turn (see the Feasting effect), so heroes who defeat the champion first leave it nothing.
 */
class CorpsebloodFeast extends AbstractBossMove
{
    public function key(): string
    {
        return 'corpseblood_feast';
    }

    public function name(): string
    {
        return 'Corpseblood Feast';
    }

    public function targetRule(): TargetRule
    {
        return TargetRule::Self;
    }

    public function resolve(ActionContext $context): void
    {
        $rain = $context->battle->effects->find($context->actor, 'blood_rain');
        $context->applyEffect($context->actor, 'feasting', 1, 1, ['champion' => $rain?->data['champion'] ?? null]);
        $context->say('seize');
    }

    public function messages(): array
    {
        return [
            'seize' => '{actor} reaches for its champion with blood-slick claws.',
        ];
    }
}

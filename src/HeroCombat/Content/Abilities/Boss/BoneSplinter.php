<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Content\Effects\AbsorbedSouls;
use OpenDominion\HeroCombat\Engine\ActionContext;

/**
 * Cast at Confused heroes only (see DragonsMiasma). Flat damage that defending does not
 * reduce; each hit that lands feeds Waaugh another soul.
 */
class BoneSplinter extends AbstractBossMove
{
    public const DAMAGE = 20;

    public function key(): string
    {
        return 'bone_splinter';
    }

    public function name(): string
    {
        return 'Bone Splinter';
    }

    public function resolve(ActionContext $context): void
    {
        $target = $context->target();
        if ($target === null) {
            return;
        }

        $result = $context->flatDamage($target, self::DAMAGE + AbsorbedSouls::bonusDamage($context->actor, $context->battle));
        $dealt = $result->amount + $result->absorbed;
        $context->say('hit', ['damage' => $dealt]);

        if ($result->amount > 0) {
            $context->applyEffect($context->actor, 'absorbed_souls');
            $context->say('absorb');
        }
    }

    public function messages(): array
    {
        return [
            'hit' => '{actor} casts Bone Splinter. No armour turns aside what erupts from within, and {target} takes {damage} damage.',
            'absorb' => '{actor} draws in a sliver of {target}\'s soul and grows stronger.',
        ];
    }
}

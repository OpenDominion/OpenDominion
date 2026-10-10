<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\CombatTag;

/**
 * Summons a defender (up to a cap) unless the target attacks to interrupt the call.
 */
class RallyTheDefenders extends AbstractBossMove
{
    public const SUMMON = 'aurelis_defender';
    public const COUNT = 1;
    public const MAX_DEFENDERS = 2;

    public function key(): string
    {
        return 'rally_the_defenders';
    }

    public function name(): string
    {
        return 'Rally the Defenders';
    }

    public function tags(): array
    {
        return [CombatTag::Summon];
    }

    public function resolve(ActionContext $context): void
    {
        if ($context->chose($context->target(), 'attack')) {
            $context->say('attack');
            return;
        }

        $living = count($context->battle->alliesOf($context->actor, includeSelf: false));
        $summonCount = min(self::COUNT, max(0, self::MAX_DEFENDERS - $living));

        if ($summonCount === 0) {
            $context->say('at_cap');
            return;
        }

        for ($i = 0; $i < $summonCount; $i++) {
            $number = count($context->battle->withTemplate(self::SUMMON, livingOnly: false)) + 1;
            $template = $context->battle->registry->enemy(self::SUMMON);
            $context->summon(self::SUMMON, "{$template->name()} #{$number}");
        }

        $context->say('summon');
    }

    public function messages(): array
    {
        return [
            'attack' => '{actor} prepares to call for reinforcements, but {target} silences him with a blow to the chest.',
            'summon' => '{actor}\'s call carries across the harbor, and a defender rushes to his aid.',
            'at_cap' => '{actor} calls again, but no more defenders answer.',
        ];
    }
}

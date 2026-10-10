<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Engine\ActionContext;

/**
 * Takes a third of the target's starting maximum health. It cannot be healed back, only
 * prevented by Cleansing on the turn it lands. Rex Lunae is exposed while casting.
 */
class HungeringMoon extends AbstractBossMove
{
    public const TRANSFORM_NAME = 'Servus Lunae';

    public function key(): string
    {
        return 'hungering_moon';
    }

    public function name(): string
    {
        return 'Curse of the Hungry Moon';
    }

    public function onDeclare(ActionContext $context): void
    {
        $context->applyEffect($context->actor, 'exposed', 1);
    }

    public function resolve(ActionContext $context): void
    {
        $battle = $context->battle;
        $target = $context->target();

        if ($context->chose($target, 'cleanse')) {
            $context->say('cleansed');
            return;
        }

        $fullHealth = $battle->effects->find($target, 'moonstruck')->data['full_health'] ?? $battle->maxHealth($target);
        $curse = $context->applyEffect($target, 'moonstruck', null, 1, ['full_health' => $fullHealth]);

        $maxHealth = $battle->maxHealth($target);
        $target->currentHealth = min($target->currentHealth, $maxHealth);
        $context->say('hit');

        if ($maxHealth <= 0) {
            $target->currentHealth = 0;
            $context->say('converted');
            return;
        }

        if ($curse !== null && empty($curse->data['turned']) && $maxHealth <= (int) ceil($fullHealth / 3)) {
            $curse->data['turned'] = true;
            $context->say('transformed');
            $target->name = self::TRANSFORM_NAME;
        }
    }

    public function messages(): array
    {
        return [
            'cleansed' => '{target} removes the curse before it takes hold.',
            'hit' => 'The curse takes hold, diminishing {target}\'s humanity.',
            'transformed' => 'Little of {target}\'s humanity remains. ' . self::TRANSFORM_NAME . ' stands in their place.',
            'converted' => self::TRANSFORM_NAME . ' joins the pack.',
        ];
    }
}

<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Content\Abilities\AbstractAttack;
use OpenDominion\HeroCombat\Engine\ActionContext;

/**
 * A charged strike: +85 damage, but defending adds 70 defense against it.
 */
class SoulRend extends AbstractAttack
{
    public const BONUS_DAMAGE = 85;
    public const DEFEND_MODIFIER = 70;

    public function key(): string
    {
        return 'soul_rend';
    }

    public function name(): string
    {
        return 'Soul Rend';
    }

    public function description(): string
    {
        return 'A devastating charged attack that must be defended.';
    }

    public function selectable(): bool
    {
        return false;
    }

    public function resolve(ActionContext $context): void
    {
        foreach ($context->targets as $target) {
            $result = $context->attack($target, $this->damageOptions($context));
            static::narrate($context, $result);

            if (!$result->target->isAlive()) {
                $context->say('kill', ['target' => $result->target->name]);
            }
        }
    }

    protected function damageOptions(ActionContext $context): array
    {
        return ['bonusDamage' => self::BONUS_DAMAGE, 'defendModifier' => self::DEFEND_MODIFIER];
    }

    public function messages(): array
    {
        return [
            'hit' => '{actor} unleashes a devastating Soul Rend for {damage} damage to {target}!',
            'evaded' => '{actor} unleashes a devastating Soul Rend for {raw} damage, but {target} evades, reducing damage to {damage}!',
            'kill' => '{actor} rips out the heart of {target} and devours their soul!',
        ];
    }
}

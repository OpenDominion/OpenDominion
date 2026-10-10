<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Damage\DamageResult;

/**
 * A weapon attack against each target, narrated with hit/evaded/countered messages.
 */
abstract class AbstractAttack extends AbstractAbility
{
    public function tags(): array
    {
        return [CombatTag::Attack];
    }

    public function resolve(ActionContext $context): void
    {
        foreach ($context->targets as $target) {
            $result = $context->attack($target, $this->damageOptions($context));
            static::narrate($context, $result);
        }
    }

    /**
     * @return array<string, mixed> DamageRequest overrides
     */
    protected function damageOptions(ActionContext $context): array
    {
        return [];
    }

    public function messages(): array
    {
        return [
            'hit' => '{actor} deals {damage} damage to {target}.',
            'evaded' => '{actor} deals {raw} damage, but {target} evades, reducing damage to {damage}.',
        ];
    }

    /**
     * Logs the standard description of a hit, including evasion, shields and counters.
     */
    public static function narrate(ActionContext $context, DamageResult $result, array $variables = []): void
    {
        $variables += [
            'target' => $result->target->name,
            'damage' => $result->amount + $result->absorbed,
            'raw' => $result->raw,
        ];

        $messages = $context->ability->messages();
        $key = $result->evaded && isset($messages['evaded']) ? 'evaded' : 'hit';
        $context->say($key, $variables);

        if ($result->absorbed > 0) {
            $context->battle->say($context->actor, "{$result->target->name}'s defenses absorb {$result->absorbed} damage.");
        }

        if ($result->wasCountered()) {
            $context->battle->say($context->actor, "{$result->target->name} counters for {$result->counterDamage()} damage.");
        }
    }
}

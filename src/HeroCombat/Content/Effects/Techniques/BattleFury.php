<?php

namespace OpenDominion\HeroCombat\Content\Effects\Techniques;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

/**
 * Technique: a basic Attack leaves you Furious for the next turn. A Furious basic Attack deals
 * bonus damage, and you have less defense whatever you do. Attack value is unchanged, so
 * Counters and attack abilities get nothing.
 *
 * Instance data: furious (bool) set after a turn you used the basic Attack.
 */
class BattleFury extends AbstractPassive
{
    public const BONUS_DAMAGE = 5;
    public const DEFENSE_PENALTY = 3;

    public function key(): string
    {
        return 'battle_fury';
    }

    public function name(): string
    {
        return 'Battle Fury';
    }

    public function description(EffectInstance $instance): string
    {
        $base = 'After an Attack, your next Attack deals +' . self::BONUS_DAMAGE . ' damage, but you have -' . self::DEFENSE_PENALTY . ' defense until you stop attacking.';

        return $this->furious($instance) ? $base . ' Currently Furious.' : $base;
    }

    public function rulesNotes(): array
    {
        return [
            'Only the basic Attack builds and uses Fury. Counters and attack abilities don\'t get the bonus.',
            'Fury doesn\'t stack: Attacking every turn keeps the bonus at +' . self::BONUS_DAMAGE . '.',
            'The defense loss applies on any turn after an Attack, even if you Defend.',
            'Any action other than Attack ends it.',
        ];
    }

    public function isActive(EffectInstance $instance, CombatantState $subject, Battle $battle): bool
    {
        return $this->furious($instance);
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        if (!$this->furious($instance)) {
            return [];
        }

        return [Modifier::flat(Stat::Defense, -self::DEFENSE_PENALTY)];
    }

    public function beforeDamageDealt(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        $request = $damage->request;
        if ($request->attacker?->id !== $instance->ownerId || $request->isCounter || $request->abilityKey !== 'attack') {
            return;
        }

        if ($this->furious($instance)) {
            $request->addBonus($this->name(), self::BONUS_DAMAGE);
        }
    }

    public function onAbilityUsed(EffectInstance $instance, ActionContext $context, Battle $battle): void
    {
        if ($context->actor->id !== $instance->ownerId) {
            return;
        }

        $attacked = $context->ability->key() === 'attack';
        $battle->afterActions($context->actor, function () use ($instance, $attacked) {
            $instance->data['furious'] = $attacked;
        });
    }

    private function furious(EffectInstance $instance): bool
    {
        return (bool) ($instance->data['furious'] ?? false);
    }
}

<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Content\Effects\Boss\BloodRain;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;

/**
 * Granted by the Ullyaoth encounter. Only usable on the turn Ullyaoth Devours, when its heart
 * is exposed: then it ignores defense, can't be Countered and deals BONUS_DAMAGE more. There
 * is one spear, so only one hero per side may wield it each turn.
 */
class TitanSpear extends AbstractAttack
{
    public const BONUS_DAMAGE = 30;

    public function key(): string
    {
        return 'titan_spear';
    }

    public function name(): string
    {
        return 'Titan Spear';
    }

    public function description(): string
    {
        return 'Drive the Titan Spear into an exposed heart.';
    }

    public function rulesNotes(): array
    {
        return [
            'Only usable while Ullyaoth\'s heart is exposed.',
            'Ignores defense, can\'t be Countered and deals ' . self::BONUS_DAMAGE . ' more damage than an attack.',
            'Only one hero can wield it each turn.',
        ];
    }

    public function onePerTeam(): bool
    {
        return true;
    }

    public function canUse(CombatantState $actor, Battle $battle): bool
    {
        return BloodRain::isDevourTurn($battle, $actor);
    }

    public function canTarget(CombatantState $actor, CombatantState $target, Battle $battle): bool
    {
        return $battle->effects->has($target, 'blood_rain');
    }

    protected function damageOptions(ActionContext $context): array
    {
        $target = $context->target();
        if ($target === null || !$context->battle->effects->has($target, 'heart_exposed')) {
            return [];
        }

        return ['ignoreDefense' => true, 'canBeCountered' => false, 'bonusDamage' => self::BONUS_DAMAGE];
    }

    public function messages(): array
    {
        return [
            'hit' => '{actor} drives the Titan Spear into {target}\'s heart for {damage} damage.',
            'evaded' => '{actor} drives the Titan Spear for {raw} damage, but {target} twists away, reducing damage to {damage}.',
        ];
    }
}

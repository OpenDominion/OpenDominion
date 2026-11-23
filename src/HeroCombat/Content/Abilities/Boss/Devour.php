<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\CombatantState;

/**
 * Ullyaoth descends on the hero marked by Blood Rain: fixed damage that ignores defense and
 * evasion. A hero Guarding the target takes the hit instead, at half. Lunging opens its chest,
 * so its heart is exposed for the whole turn.
 */
class Devour extends AbstractBossMove
{
    public const DAMAGE = 60;
    public const GUARDED_SHARE = 0.5;

    public function key(): string
    {
        return 'devour';
    }

    public function name(): string
    {
        return 'Devour';
    }

    public function onDeclare(ActionContext $context): void
    {
        $context->applyEffect($context->actor, 'heart_exposed', 1);
    }

    public function resolve(ActionContext $context): void
    {
        $target = $context->target();
        if ($target === null) {
            return;
        }

        $guarded = $this->guardian($context, $target) !== null;
        $amount = $guarded ? (int) round(self::DAMAGE * self::GUARDED_SHARE) : self::DAMAGE;

        $result = $context->flatDamage($target, $amount);
        $context->say($guarded ? 'guarded' : 'hit', [
            'target' => $result->target->name,
            'damage' => $result->amount + $result->absorbed,
        ]);
    }

    /**
     * The living hero Guarding the target this turn, if any.
     */
    protected function guardian(ActionContext $context, CombatantState $target): ?CombatantState
    {
        $cover = $context->battle->effects->find($target, 'covered');
        $guardian = $cover !== null ? $context->battle->combatant($cover->sourceId) : null;

        return $guardian !== null && $guardian->isAlive() ? $guardian : null;
    }

    public function messages(): array
    {
        return [
            'hit' => '{actor} parts the clouds and devours {target} for {damage} damage!',
            'guarded' => '{actor} parts the clouds and lunges, but {target} braces its jaws open, taking {damage} damage.',
        ];
    }
}

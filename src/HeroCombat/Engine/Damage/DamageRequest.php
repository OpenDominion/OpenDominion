<?php

namespace OpenDominion\HeroCombat\Engine\Damage;

use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;

/**
 * Describes one damaging hit before any rules are applied.
 */
final class DamageRequest
{
    /**
     * @param int|null $flatDamage fixed damage instead of the attack-vs-defense formula
     * @param int $defendModifier extra defense the target gains while Defending
     * @param CombatTag[] $tags
     */
    public function __construct(
        public ?CombatantState $attacker,
        public CombatantState $target,
        public ?string $abilityKey = null,
        public ?int $flatDamage = null,
        public int $bonusDamage = 0,
        public float $multiplier = 1.0,
        public int $hits = 1,
        public bool $canEvade = true,
        public bool $canBeCountered = true,
        public bool $ignoreDefense = false,
        public bool $ignoreShield = false,
        public bool $bypassLethalSave = false,
        public int $defendModifier = 0,
        public bool $isCounter = false,
        public array $tags = [CombatTag::Attack],
    ) {
    }

    public function hasTag(CombatTag $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }
}

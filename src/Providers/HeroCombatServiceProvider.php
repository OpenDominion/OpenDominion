<?php

namespace OpenDominion\Providers;

use Illuminate\Support\ServiceProvider;
use OpenDominion\HeroCombat\Content\Abilities;
use OpenDominion\HeroCombat\Content\Ai\WeightedStrategy;
use OpenDominion\HeroCombat\Content\Effects;
use OpenDominion\HeroCombat\Content\Enemies;
use OpenDominion\HeroCombat\Content\Encounters;
use OpenDominion\HeroCombat\Registry\CombatRegistry;

/**
 * Registers all hero combat content. To add an ability, effect, enemy or encounter,
 * write the class and add it to the matching list below.
 */
class HeroCombatServiceProvider extends ServiceProvider
{
    /** @var array<int, class-string> */
    public const ABILITIES = [
        Abilities\Attack::class,
        Abilities\Defend::class,
        Abilities\Focus::class,
        Abilities\Counter::class,
        Abilities\Recover::class,
        Abilities\Pass::class,
        Abilities\Fortify::class,
        Abilities\Forge::class,
        Abilities\TacticalAwareness::class,
        Abilities\CombatAnalysis::class,
        Abilities\ShadowStrike::class,
        Abilities\VolatileMixture::class,
        Abilities\BladeFlurry::class,
        Abilities\Demolish::class,
        Abilities\GreatFlood::class,
        Abilities\Cleanse::class,
        Abilities\Boss\Broadside::class,
        Abilities\Boss\RallyTheDefenders::class,
        Abilities\Boss\AdmiralsChallenge::class,
        Abilities\Boss\Bloodrend::class,
        Abilities\Boss\FrostGrip::class,
        Abilities\Boss\WintersBreath::class,
    ];

    /** @var array<int, class-string> */
    public const EFFECTS = [
        Effects\Stances\Defending::class,
        Effects\Stances\Countering::class,
        Effects\Stances\Recovering::class,
        Effects\Focused::class,
        Effects\Shield::class,
        Effects\Forged::class,
        Effects\Analyzed::class,
        Effects\Outmaneuvered::class,
        Effects\FrostbiteStack::class,
        Effects\Provoked::class,
        Effects\Covered::class,
        Effects\Passives\Enrage::class,
        Effects\Passives\Rally::class,
        Effects\Passives\LastStand::class,
        Effects\Passives\ArcaneShield::class,
        Effects\Passives\Weakened::class,
        Effects\Passives\Retribution::class,
        Effects\Passives\Lifesteal::class,
        Effects\Passives\Elusive::class,
        Effects\Passives\Mending::class,
        Effects\Passives\Channeling::class,
        Effects\Passives\Hardiness::class,
        Effects\Passives\CrushingBlow::class,
        Effects\Passives\Frostbite::class,
        Effects\Freezing::class,
        Effects\Frozen::class,
        Effects\Severed::class,
        Effects\Boss\AdmiralsOrders::class,
        Effects\Boss\SnowWitchCurse::class,
        Effects\Boss\TomeOfPowerChapters::class,
        Effects\Boss\PowerSource::class,
    ];

    /** @var array<int, class-string> */
    public const ENEMIES = [
        Enemies\EvilTwin::class,
        Enemies\AdmiralVaros::class,
        Enemies\AurelisDefender::class,
        Enemies\ElizaHeartOfIce::class,
        Enemies\LichKing::class,
        Enemies\TomeOfPower::class,
    ];

    /** @var array<int, class-string> */
    public const ENCOUNTERS = [
        Encounters\EvilTwinEncounter::class,
        Encounters\AdmiralVarosEncounter::class,
        Encounters\HeartOfIceEncounter::class,
        Encounters\LichKingEncounter::class,
    ];

    public function register(): void
    {
        $this->app->singleton(CombatRegistry::class, fn () => self::buildRegistry());
    }

    public static function buildRegistry(): CombatRegistry
    {
        $registry = new CombatRegistry();

        foreach (self::ABILITIES as $class) {
            $registry->registerAbility(new $class());
        }
        foreach (self::EFFECTS as $class) {
            $registry->registerEffect(new $class());
        }
        foreach (WeightedStrategy::presets() as $strategy) {
            $registry->registerStrategy($strategy);
        }
        foreach (self::ENEMIES as $class) {
            $registry->registerEnemy(new $class());
        }
        foreach (self::ENCOUNTERS as $class) {
            $registry->registerEncounter(new $class());
        }

        return $registry;
    }
}

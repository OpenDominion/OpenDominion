<?php

namespace OpenDominion\Tests\Unit\HeroCombat;

use OpenDominion\HeroCombat\Content\Effects\Exposed;
use OpenDominion\HeroCombat\Content\Effects\Invigorated;
use OpenDominion\HeroCombat\Content\Effects\Marked;
use OpenDominion\HeroCombat\Content\Effects\Staggered;
use OpenDominion\HeroCombat\Content\Effects\Techniques\Backstab;
use OpenDominion\HeroCombat\Content\Effects\Techniques\BattleFury;
use OpenDominion\HeroCombat\Content\Effects\Techniques\Feint;
use OpenDominion\HeroCombat\Content\Effects\Techniques\Restoration;
use OpenDominion\HeroCombat\Engine\ActionValidator;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use PHPUnit\Framework\TestCase;

class TechniquesTest extends TestCase
{
    use BuildsBattles;

    /**
     * Hero vs Foe. An 'idle' foe has no abilities and passes every turn.
     */
    private function duel(string $technique, string $foeAi = 'attack', array $heroStats = [], array $foeStats = []): Battle
    {
        $battle = BattleBuilder::make()
            ->hero('Hero', stats: $heroStats)
            ->npc(
                'Foe',
                stats: array_merge(['health' => 500], $foeStats),
                abilities: $foeAi === 'idle' ? [] : ['attack', 'defend', 'focus', 'counter', 'recover'],
                ai: $foeAi === 'idle' ? 'attack' : $foeAi,
            )
            ->build();
        $battle->effects->apply($this->named($battle, 'Hero'), $technique);

        return $battle;
    }

    private function damageTo(Battle $battle, string $name, callable $act): int
    {
        $before = $this->named($battle, $name)->currentHealth;
        $act();

        return $before - $this->named($battle, $name)->currentHealth;
    }

    // ------------------------------------------------------------------
    // Technique I
    // ------------------------------------------------------------------

    public function testBattleFuryBoostsTheNextAttackWithoutStackingAndResets(): void
    {
        $battle = $this->duel('battle_fury', 'idle');
        $hero = $this->named($battle, 'Hero');

        $damages = [];
        for ($i = 0; $i < 3; $i++) {
            $this->queue($battle, 'Hero', 'attack', 'Foe');
            $damages[] = $this->damageTo($battle, 'Foe', fn () => $this->resolveTurn($battle));
        }

        $bonus = BattleFury::BONUS_DAMAGE;
        $this->assertEquals([20, 20 + $bonus, 20 + $bonus], $damages, 'One stack');
        $this->assertEquals(40, $battle->stat($hero, Stat::Attack), 'Attack value is unchanged');
        $this->assertEquals(20 - BattleFury::DEFENSE_PENALTY, $battle->stat($hero, Stat::Defense));

        $this->queue($battle, 'Hero', 'defend');
        $this->resolveTurn($battle);
        $this->assertEquals(20, $battle->stat($hero, Stat::Defense));
    }

    public function testBattleFuryShowsAsActiveOnlyWhileFurious(): void
    {
        $battle = $this->duel('battle_fury', 'idle');
        $hero = $this->named($battle, 'Hero');
        $active = fn () => $battle->registry->effect('battle_fury')->isActive($battle->effects->find($hero, 'battle_fury'), $hero, $battle);

        $this->assertFalse($active());

        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->resolveTurn($battle);
        $this->assertTrue($active());

        $this->queue($battle, 'Hero', 'defend');
        $this->resolveTurn($battle);
        $this->assertFalse($active());
    }

    public function testBattleFuryDoesNotBoostCounters(): void
    {
        $battle = $this->pvp('battle_fury');
        $foe = $this->named($battle, 'Foe');

        $this->turn($battle, ['attack', 'Foe'], ['defend']);
        $health = $foe->currentHealth;
        $this->turn($battle, ['counter'], ['attack', 'Hero']);

        $this->assertEquals(40 + 10 - 20, $health - $foe->currentHealth, 'A plain Counter while Furious');
    }

    public function testRestorationHealsMostNowAndTheRestOverTwoTurns(): void
    {
        $battle = $this->duel('restoration', 'idle');
        $hero = $this->named($battle, 'Hero');
        $hero->currentHealth = 40;

        $this->declare($battle, 'Hero', 'recover');
        $this->assertTrue($battle->effects->has($hero, 'exposed'), 'Recovering still leaves the healer Exposed');
        $battle->state->intents = [];

        $this->queue($battle, 'Hero', 'recover');
        $this->resolveTurn($battle);
        $this->assertEquals(40 + 16, $hero->currentHealth, '80% of 20 immediately');

        $health = [];
        for ($i = 0; $i < 3; $i++) {
            $this->queue($battle, 'Hero', 'defend');
            $this->resolveTurn($battle);
            $health[] = $hero->currentHealth;
        }

        $this->assertEquals([60, 64, 64], $health, '20% at the start of each of the next two turns');
        $this->assertFalse($battle->effects->has($hero, 'restoring'));
    }

    public function testRecastingRestorationStartsAFreshSetOfTicks(): void
    {
        $battle = $this->duel('restoration', 'idle');
        $hero = $this->named($battle, 'Hero');
        $hero->currentHealth = 20;

        foreach (['recover', 'defend', 'recover'] as $ability) {
            $this->queue($battle, 'Hero', $ability);
            $this->resolveTurn($battle);
        }

        $this->assertEquals(20 + 16 + 4 + 4 + 16, $hero->currentHealth, 'Both ticks of the first heal, then the recast');
        $this->assertEquals(Restoration::TICKS, $battle->effects->find($hero, 'restoring')->remainingTurns, 'The recast starts a fresh set of ticks');
    }

    public function testShadowDanceGrantsFocusOnEvade(): void
    {
        $battle = $this->duel('shadow_dance', foeStats: ['attack' => 60]);
        $hero = $this->named($battle, 'Hero');
        $battle->evasionRoll = fn ($target) => $target->id === $hero->id;

        $this->queue($battle, 'Hero', 'defend');
        $taken = $this->damageTo($battle, 'Hero', fn () => $this->resolveTurn($battle));

        $this->assertEquals(10, $taken, 'An evaded hit still deals half');
        $this->assertTrue($battle->effects->has($hero, 'focused'));
        $this->assertEquals(1, $battle->effects->stacks($hero, 'focused'));
    }

    public function testShadowDanceNeedsAnEvade(): void
    {
        $battle = $this->duel('shadow_dance', foeStats: ['attack' => 60]);
        $hero = $this->named($battle, 'Hero');
        $battle->evasionRoll = fn () => false;

        $this->queue($battle, 'Hero', 'defend');
        $this->resolveTurn($battle);

        $this->assertFalse($battle->effects->has($hero, 'focused'));
        $this->assertEquals(0, $battle->stat($hero, Stat::Evasion), 'Defend no longer adds evasion');
    }

    public function testBackstabHitsFocusedTargetsHarder(): void
    {
        $battle = $this->duel('backstab', 'idle');

        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->assertEquals(20, $this->damageTo($battle, 'Foe', fn () => $this->resolveTurn($battle)));

        $battle->effects->apply($this->named($battle, 'Foe'), 'focused');
        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->assertEquals(20 + Backstab::BONUS_DAMAGE, $this->damageTo($battle, 'Foe', fn () => $this->resolveTurn($battle)));
    }

    public function testBackstabCountsFocusFromTheStartOfTheTurn(): void
    {
        $battle = BattleBuilder::make()
            ->npc('Foe', stats: ['health' => 500], abilities: ['attack'], ai: 'attack')
            ->hero('Hero')
            ->build();
        $hero = $this->named($battle, 'Hero');
        $foe = $this->named($battle, 'Foe');
        $battle->effects->apply($hero, 'backstab');
        $battle->effects->apply($foe, 'focused');
        $this->assertLessThan($hero->id, $foe->id, 'The Foe acts first and spends its Focus before the Hero strikes');

        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $damage = $this->damageTo($battle, 'Foe', fn () => $this->resolveTurn($battle));

        $this->assertFalse($battle->effects->has($foe, 'focused'));
        $this->assertEquals(20 + Backstab::BONUS_DAMAGE, $damage);
    }

    public function testFeintAddsEvasionOnlyOnATurnYouAttack(): void
    {
        $battle = $this->pvp('feint');
        $seen = [];
        $battle->evasionRoll = function ($target, $evasion) use (&$seen) {
            $seen[] = [$target->name, $evasion];

            return false;
        };

        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->queue($battle, 'Foe', 'attack', 'Hero');
        $this->resolveTurn($battle);
        $this->assertContains(['Hero', Feint::EVASION_BONUS], $seen, 'Attacks landing the same turn face the bonus');

        $seen = [];
        $this->queue($battle, 'Hero', 'counter');
        $this->queue($battle, 'Foe', 'attack', 'Hero');
        $this->resolveTurn($battle);
        $this->assertContains(['Hero', 0], $seen);
        $this->assertFalse($battle->effects->has($this->named($battle, 'Hero'), 'feint_status'), 'No status left behind');
    }

    // ------------------------------------------------------------------
    // Technique II
    // ------------------------------------------------------------------

    /**
     * Hero vs Foe, both human so each turn's actions are queued.
     */
    private function pvp(string $technique, array $foeStats = []): Battle
    {
        $battle = BattleBuilder::make()
            ->hero('Hero')
            ->hero('Foe', team: 2, stats: array_merge(['health' => 500], $foeStats))
            ->build();
        $battle->effects->apply($this->named($battle, 'Hero'), $technique);
        $battle->evasionRoll = fn () => false;

        return $battle;
    }

    /**
     * Queues both sides and resolves the turn.
     */
    private function turn(Battle $battle, array $hero, array $foe): void
    {
        $this->queue($battle, 'Hero', ...$hero);
        $this->queue($battle, 'Foe', ...$foe);
        $this->resolveTurn($battle);
    }

    public function testShieldBashStaggersAnAttackerForTwoTurnsEvenWhenTheHitIsBlocked(): void
    {
        $battle = $this->pvp('shield_bash');
        $hero = $this->named($battle, 'Hero');
        $foe = $this->named($battle, 'Foe');
        $attack = $battle->stat($foe, Stat::Attack);

        $this->turn($battle, ['defend'], ['attack', 'Hero']);
        $this->assertEquals(100, $hero->currentHealth, 'Defend blocked the hit');
        $this->assertEquals($attack - Staggered::ATTACK, $battle->stat($foe, Stat::Attack));
        $this->assertTrue((new ActionValidator($battle))->canPerform($foe, 'counter'), 'Staggered no longer blocks Counter');

        $this->turn($battle, ['focus'], ['focus']);
        $this->assertEquals($attack - Staggered::ATTACK, $battle->stat($foe, Stat::Attack), 'Still Staggered on the second turn');

        $this->turn($battle, ['defend'], ['defend']);
        $this->assertFalse($battle->effects->has($foe, 'staggered'));
        $this->assertEquals($attack, $battle->stat($foe, Stat::Attack));
    }

    public function testShieldBashNeedsDefend(): void
    {
        $battle = $this->pvp('shield_bash');

        $this->turn($battle, ['focus'], ['attack', 'Hero']);

        $this->assertFalse($battle->effects->has($this->named($battle, 'Foe'), 'staggered'));
    }

    public function testRiposteMarksTheAttackerItCounters(): void
    {
        $battle = $this->pvp('riposte');
        $foe = $this->named($battle, 'Foe');

        $this->turn($battle, ['counter'], ['attack', 'Hero']);
        $this->assertTrue($battle->effects->has($foe, 'marked'));

        $health = $foe->currentHealth;
        $this->turn($battle, ['attack', 'Foe'], ['focus']);
        $this->assertEquals(20 + Marked::BONUS_DAMAGE, $health - $foe->currentHealth);
        $this->assertTrue($battle->effects->has($foe, 'marked'), 'Marked lasts two turns');

        $this->turn($battle, ['defend'], ['defend']);
        $this->assertFalse($battle->effects->has($foe, 'marked'));
    }

    public function testSpellbrandMarksOnlyOnAFocusedHit(): void
    {
        $battle = $this->pvp('spellbrand');
        $foe = $this->named($battle, 'Foe');

        $this->turn($battle, ['attack', 'Foe'], ['focus']);
        $this->assertFalse($battle->effects->has($foe, 'marked'), 'Unfocused');

        $this->turn($battle, ['focus'], ['focus']);
        $battle->evasionRoll = fn ($target) => $target->id === $foe->id;
        $this->turn($battle, ['attack', 'Foe'], ['recover']);
        $this->assertFalse($battle->effects->has($foe, 'marked'), 'Evaded');

        $battle->evasionRoll = fn () => false;
        $this->turn($battle, ['focus'], ['defend']);
        $this->turn($battle, ['attack', 'Foe'], ['attack', 'Hero']);
        $this->assertTrue($battle->effects->has($foe, 'marked'));
    }

    public function testSecondWindInvigoratesTheHealerAndTheHealedAlly(): void
    {
        $battle = BattleBuilder::make()
            ->hero('Hero')
            ->hero('Ally')
            ->hero('Foe', team: 2, stats: ['health' => 500])
            ->build();
        $hero = $this->named($battle, 'Hero');
        $ally = $this->named($battle, 'Ally');
        $battle->effects->apply($hero, 'second_wind');
        $battle->evasionRoll = fn () => false;

        $this->queue($battle, 'Hero', 'recover');
        $this->queue($battle, 'Ally', 'defend');
        $this->queue($battle, 'Foe', 'focus');
        $this->resolveTurn($battle);
        $this->assertEquals(40 + Invigorated::ATTACK_BONUS, $battle->stat($hero, Stat::Attack));
        $this->assertFalse($battle->effects->has($ally, 'invigorated'), 'Only the ally you heal');

        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->queue($battle, 'Ally', 'defend');
        $this->queue($battle, 'Foe', 'defend');
        $this->resolveTurn($battle);
        $this->assertFalse($battle->effects->has($hero, 'invigorated'));

        $this->queue($battle, 'Hero', 'recover', 'Ally');
        $this->queue($battle, 'Ally', 'defend');
        $this->queue($battle, 'Foe', 'focus');
        $this->resolveTurn($battle);
        $this->assertTrue($battle->effects->has($hero, 'invigorated'));
        $this->assertTrue($battle->effects->has($ally, 'invigorated'));
    }

    // ------------------------------------------------------------------
    // Shared statuses
    // ------------------------------------------------------------------

    public function testMarkedPaysOffForAnyAttackerButNotBlockedHitsOrCounters(): void
    {
        $battle = BattleBuilder::make()
            ->hero('Hero')
            ->hero('Ally')
            ->hero('Foe', team: 2, stats: ['health' => 500])
            ->build();
        $foe = $this->named($battle, 'Foe');
        $battle->evasionRoll = fn () => false;
        $battle->effects->apply($foe, 'marked');

        $this->assertEquals(20 + Marked::BONUS_DAMAGE, $this->damageTo($battle, 'Foe', fn () => $this->perform($battle, 'Ally', 'attack', 'Foe')));

        $this->declare($battle, 'Foe', 'defend');
        $this->assertEquals(40 + Marked::BONUS_DAMAGE - 40, $this->damageTo($battle, 'Foe', fn () => $this->perform($battle, 'Hero', 'attack', 'Foe')), 'Added before defense, so Defend takes its share');

        $battle->effects->removeByKey($foe, 'defending');
        $counter = $battle->damage->counterAttack($this->named($battle, 'Hero'), $foe);
        $this->assertEquals(40 + 10 - 20, $counter->amount, 'Counters get no bonus');
    }

    public function testTechniquesAndStatusesAreVisible(): void
    {
        $registry = \OpenDominion\Providers\HeroCombatServiceProvider::buildRegistry();

        foreach (['feint', 'shield_bash', 'riposte', 'spellbrand', 'second_wind', 'exposed', 'marked', 'staggered', 'invigorated'] as $key) {
            $this->assertTrue($registry->effect($key)->visible(), $key);
        }
    }
}

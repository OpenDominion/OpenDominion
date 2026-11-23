<?php

namespace OpenDominion\Tests\Unit\HeroCombat\Bosses;

use OpenDominion\HeroCombat\Content\Abilities\Boss\Devour;
use OpenDominion\HeroCombat\Content\Abilities\TitanSpear;
use OpenDominion\HeroCombat\Content\Effects\Bleeding;
use OpenDominion\HeroCombat\Content\Effects\Boss\BloodRain;
use OpenDominion\HeroCombat\Content\Effects\Boss\Feasting;
use OpenDominion\HeroCombat\Content\Encounters\UllyaothEncounter;
use OpenDominion\HeroCombat\Engine\ActionValidator;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\HeroCombat\Engine\Events\EventType;
use OpenDominion\HeroCombat\Engine\Events\MessageKind;
use OpenDominion\HeroCombat\Engine\IntentDecider;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\HeroCombat\Engine\TurnResolver;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use OpenDominion\Tests\Unit\HeroCombat\Support\ScriptedRandomSource;
use PHPUnit\Framework\TestCase;

/**
 * World-Eater Ullyaoth: Blood Rain tells Devour, the Titan Spear answers it, Corpseblood Feast
 * eats a standing champion, and Hemorrhage leaves heroes Bleeding.
 */
class UllyaothTest extends TestCase
{
    use BuildsBattles;

    protected const HERO_ABILITIES = ['attack', 'defend', 'focus', 'counter', 'recover', 'titan_spear'];

    protected Battle $battle;

    protected ScriptedRandomSource $random;

    protected function setUp(): void
    {
        parent::setUp();
        $this->random = new ScriptedRandomSource();
        $this->battle = BattleBuilder::make('ullyaoth')
            ->withRoster()
            ->hero('Alice', abilities: self::HERO_ABILITIES)
            ->hero('Bob', abilities: self::HERO_ABILITIES)
            ->build($this->random);
    }

    protected function ullyaoth(): CombatantState
    {
        return $this->named($this->battle, 'World-Eater Ullyaoth');
    }

    protected function champion(): CombatantState
    {
        return $this->named($this->battle, 'Bloodbound Orc');
    }

    protected function rain(): EffectInstance
    {
        return $this->battle->effects->find($this->ullyaoth(), 'blood_rain');
    }

    /**
     * Runs the end of the given turn: TurnEnd hooks, then duration ticking.
     */
    protected function endTurn(int $turn): void
    {
        $this->battle->state->turn = $turn;
        $this->battle->dispatcher->runEverywhere(Hook::TurnEnd, $this->battle);
        $this->battle->effects->tickTurnEnd();
    }

    /**
     * Marks `$name` for a Devour on the current turn.
     */
    protected function devourThisTurn(string $name): void
    {
        $this->rain()->data['devour_turn'] = $this->battle->turn();
        $this->rain()->data['target'] = $this->named($this->battle, $name)->id;
    }

    /**
     * @return array<int, array<string, mixed>> damage events for an ability, in order
     */
    protected function damageEvents(string $ability): array
    {
        $events = [];
        foreach ($this->battle->log->entries() as $entry) {
            foreach ($entry->events as $event) {
                if ($event->type === EventType::Damage && $event->payload['ability'] === $ability) {
                    $events[] = $event->payload;
                }
            }
        }

        return $events;
    }

    /**
     * Lets the turn resolve with Ullyaoth and its champion acting on their own.
     */
    protected function automateEnemies(): void
    {
        foreach ([$this->ullyaoth(), $this->champion()] as $enemy) {
            $enemy->automated = true;
        }
    }

    // Blood Rain and Devour

    public function testBloodRainMarksAHeroForADevourOnTheNextTurn(): void
    {
        $this->random->chances = [true, false];
        $this->endTurn(1);

        $this->assertEquals(2, $this->rain()->data['devour_turn']);

        $this->battle->state->turn = 2;
        $intent = (new IntentDecider($this->battle, new ActionValidator($this->battle)))->decide($this->ullyaoth());
        $this->assertEquals('devour', $intent->abilityKey);
        $this->assertEquals($this->rain()->data['target'], $intent->targetId);
    }

    public function testBothTellsAreLoggedAsTelegraphs(): void
    {
        $this->random->chances = [true];
        $this->endTurn(1);
        $this->random->chances = [true];
        $this->endTurn(2);

        $kinds = [];
        foreach ($this->battle->log->entries() as $entry) {
            foreach ($entry->events as $event) {
                if ($event->type === EventType::Message) {
                    $kinds[] = $event->payload['kind'];
                }
            }
        }

        $this->assertEquals([MessageKind::Telegraph->value, MessageKind::Telegraph->value], $kinds);
        $this->assertArrayHasKey('devour_turn', $this->rain()->data);
        $this->assertArrayHasKey('feast_turn', $this->rain()->data);
    }

    public function testNoDevourIsToldWhenTheRollFails(): void
    {
        $this->random->chances = [false, false];
        $this->endTurn(1);

        $this->assertArrayNotHasKey('devour_turn', $this->rain()->data);
    }

    public function testDevoursAlternateHeroesAndKeepTheirDistance(): void
    {
        $this->random->defaultChance = true;
        $this->endTurn(1);
        $first = $this->rain()->data['target'];

        $this->endTurn(2);
        $this->endTurn(3);
        $this->assertEquals(2, $this->rain()->data['devour_turn'], 'A Devour on turn 4 would come too soon after turn 2');

        $this->endTurn(4);
        $this->assertEquals(5, $this->rain()->data['devour_turn']);
        $this->assertNotEquals($first, $this->rain()->data['target']);
        $this->assertGreaterThanOrEqual(BloodRain::DEVOUR_MIN_GAP, 5 - 2);
    }

    public function testDevourIgnoresDefenseAndExposesTheHeart(): void
    {
        $alice = $this->named($this->battle, 'Alice');
        $this->devourThisTurn('Alice');

        $this->declare($this->battle, 'World-Eater Ullyaoth', 'devour');
        $this->assertTrue($this->battle->effects->has($this->ullyaoth(), 'heart_exposed'));

        $this->perform($this->battle, 'World-Eater Ullyaoth', 'devour', 'Alice');
        $this->assertEquals(100 - Devour::DAMAGE, $alice->currentHealth);
    }

    public function testAGuardTakesTheDevourAtHalfWhileTheSpearPiercesTheHeart(): void
    {
        $this->automateEnemies();
        $this->devourThisTurn('Alice');
        $this->queue($this->battle, 'Alice', 'titan_spear', 'World-Eater Ullyaoth');
        $this->queue($this->battle, 'Bob', 'defend', 'Alice');

        $this->resolveTurn($this->battle);

        [$devour] = $this->damageEvents('devour');
        $this->assertEquals($this->named($this->battle, 'Bob')->id, $devour['target']);
        $this->assertEquals((int) round(Devour::DAMAGE * Devour::GUARDED_SHARE), $devour['amount']);

        [$spear] = $this->damageEvents('titan_spear');
        $this->assertEquals(40 + TitanSpear::BONUS_DAMAGE, $spear['amount'], 'Ignores Ullyaoth\'s defense');
        $this->assertFalse($spear['counter']);
    }

    public function testSpearingUllyaothAsTheLastHeroFallsIsADraw(): void
    {
        $this->automateEnemies();
        $this->random->defaultChance = false;
        $this->battle->setHealth($this->named($this->battle, 'Alice'), 0);
        (new TurnResolver())->processDeaths($this->battle);
        $this->named($this->battle, 'Bob')->currentHealth = 10;
        $this->ullyaoth()->currentHealth = 50;
        $this->devourThisTurn('Bob');
        $this->queue($this->battle, 'Bob', 'titan_spear', 'World-Eater Ullyaoth');

        $this->resolveTurn($this->battle);

        $this->assertFalse($this->ullyaoth()->isAlive());
        $this->assertFalse($this->champion()->isAlive(), 'The orc collapses with Ullyaoth');
        $this->assertTrue($this->battle->state->finished);
        $this->assertNull($this->battle->state->winningTeam);
    }

    // Titan Spear

    public function testTheSpearIsOnlyUsableOnADevourTurn(): void
    {
        $alice = $this->named($this->battle, 'Alice');
        $validator = new ActionValidator($this->battle);

        $this->assertFalse($validator->canPerform($alice, 'titan_spear'));

        $this->devourThisTurn('Bob');
        $this->assertTrue($validator->canPerform($alice, 'titan_spear'));
    }

    public function testTheSpearCanOnlyTargetUllyaoth(): void
    {
        $alice = $this->named($this->battle, 'Alice');
        $spear = $this->battle->registry->ability('titan_spear');

        $this->assertTrue($this->battle->targets->isValidChoice($alice, $spear, $this->ullyaoth()));
        $this->assertFalse($this->battle->targets->isValidChoice($alice, $spear, $this->champion()));
        $this->assertTrue($spear->onePerTeam());
    }

    // Corpseblood Feast

    public function testAFeastIsOnlyToldWhileTheChampionStands(): void
    {
        $this->battle->setHealth($this->champion(), 0);
        (new TurnResolver())->processDeaths($this->battle);

        $this->random->chances = [false, true];
        $this->endTurn(1);

        $this->assertArrayNotHasKey('feast_turn', $this->rain()->data);
    }

    public function testAFeastIsNeverToldForADevourTurn(): void
    {
        $this->random->chances = [true, true];
        $this->endTurn(1);

        $this->assertEquals(2, $this->rain()->data['devour_turn']);

        $this->assertArrayNotHasKey('feast_turn', $this->rain()->data);
    }

    public function testTheFeastDevoursAStandingChampion(): void
    {
        $this->automateEnemies();
        $this->random->defaultChance = false;
        $this->ullyaoth()->currentHealth = 200;
        $this->rain()->data['feast_turn'] = 1;
        $this->queue($this->battle, 'Alice', 'defend');
        $this->queue($this->battle, 'Bob', 'defend');

        $this->resolveTurn($this->battle);

        $this->assertFalse($this->champion()->isAlive());
        $this->assertEquals(200 + Feasting::HEAL, $this->ullyaoth()->currentHealth);
        $this->assertEquals(25 + Feasting::ATTACK_BONUS, $this->battle->stat($this->ullyaoth(), Stat::Attack));
        $this->assertArrayHasKey('countdown', $this->battle->effects->find($this->champion(), 'undying')->data, 'Undying brings it back');
    }

    public function testDefeatingTheChampionOnTheFeastTurnLeavesNothingToEat(): void
    {
        $this->automateEnemies();
        $this->random->defaultChance = false;
        $this->ullyaoth()->currentHealth = 200;
        $this->champion()->currentHealth = 20;
        $this->rain()->data['feast_turn'] = 1;
        $this->queue($this->battle, 'Alice', 'attack', 'Bloodbound Orc');
        $this->queue($this->battle, 'Bob', 'defend');

        $this->resolveTurn($this->battle);

        $this->assertFalse($this->champion()->isAlive());
        $this->assertEquals(200, $this->ullyaoth()->currentHealth);
        $this->assertEquals(25, $this->battle->stat($this->ullyaoth(), Stat::Attack));
    }

    // Hemorrhage and Bleeding

    public function testHemorrhageLeavesTheTargetBleedingEachTurn(): void
    {
        $alice = $this->named($this->battle, 'Alice');
        $this->perform($this->battle, 'World-Eater Ullyaoth', 'hemorrhage', 'Alice');
        $afterHit = $alice->currentHealth;

        $this->assertTrue($this->battle->effects->has($alice, 'bleeding'));

        $this->random->defaultChance = false;
        $this->endTurn(1);
        $this->assertEquals($afterHit - Bleeding::DAMAGE, $alice->currentHealth);
    }

    public function testBleedingStacksUpToItsCap(): void
    {
        $alice = $this->named($this->battle, 'Alice');
        for ($i = 0; $i < Bleeding::MAX_STACKS + 1; $i++) {
            $this->battle->effects->apply($alice, 'bleeding');
        }

        $this->random->defaultChance = false;
        $this->endTurn(1);

        $this->assertEquals(100 - Bleeding::DAMAGE * Bleeding::MAX_STACKS, $alice->currentHealth);
    }

    public function testAGuardCannotTakeTheBleeding(): void
    {
        $alice = $this->named($this->battle, 'Alice');
        $bob = $this->named($this->battle, 'Bob');
        $this->battle->effects->apply($alice, 'bleeding');
        $this->battle->effects->apply($alice, 'covered', 1, 1, [], $bob);

        $this->random->defaultChance = false;
        $this->endTurn(1);

        $this->assertEquals(100 - Bleeding::DAMAGE, $alice->currentHealth);
        $this->assertEquals(100, $bob->currentHealth);
    }

    public function testBleedingWearsOff(): void
    {
        $alice = $this->named($this->battle, 'Alice');
        $this->battle->effects->apply($alice, 'bleeding');

        $this->random->defaultChance = false;
        for ($turn = 1; $turn <= Bleeding::TURNS; $turn++) {
            $this->endTurn($turn);
        }

        $this->assertFalse($this->battle->effects->has($alice, 'bleeding'));
        $this->assertEquals(100 - Bleeding::DAMAGE * Bleeding::TURNS, $alice->currentHealth);
    }

    // Encounter

    public function testTheEncounterNeedsTwoHeroesAndGrantsTheSpear(): void
    {
        $encounter = new UllyaothEncounter();

        $this->assertEquals(2, $encounter->minPartySize());
        $this->assertEquals(2, $encounter->maxPartySize());
        $this->assertEquals(['titan_spear'], $encounter->playerGrants($this->named($this->battle, 'Alice'), []));
        $this->assertTrue($this->battle->effects->has($this->champion(), 'undying'));
    }
}

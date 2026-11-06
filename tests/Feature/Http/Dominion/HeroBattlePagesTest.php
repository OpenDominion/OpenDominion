<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\HeroCombat\Content\Effects\Techniques\Backstab;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroCombatant;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Tests\AbstractTestCase;
use OpenDominion\Tests\Traits\EnablesFullHeroProgression;

class HeroBattlePagesTest extends AbstractTestCase
{
    use DatabaseTransactions;
    use EnablesFullHeroProgression;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableFullHeroProgression();
    }

    protected function heroDominion(): Dominion
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-7 days');
        $dominion = $this->createDominionWithLegacyStats($user, $round, Race::where('name', 'Human')->firstOrFail());
        Hero::create([
            'dominion_id' => $dominion->id,
            'name' => 'Page Hero',
            'class' => 'blacksmith',
            'experience' => 2000,
            'class_data' => [],
        ]);
        $this->selectDominion($dominion->fresh());

        return $dominion->fresh();
    }

    /**
     * Locks in one hero's order through the route.
     */
    protected function lockIn(HeroBattle $battle, HeroCombatant $combatant, string $action, ?int $target = null): void
    {
        $this->post(route('dominion.heroes.battles.orders'), [
            'battle' => $battle->id,
            'orders' => [$combatant->id => ['action' => $action, 'target' => $target]],
        ])->assertRedirect(route('dominion.heroes.battles'));
    }

    public function testPracticePageListsRegisteredEncounters(): void
    {
        $this->heroDominion();

        $this->get(route('dominion.heroes.battles.practice'))
            ->assertOk()
            ->assertSee('Admiral Varos')
            ->assertSee('Eliza, the Heart of Ice')
            ->assertSee('The Lich King');
    }

    public function testActiveBattlePageShowsSidesEffectsAndActions(): void
    {
        $dominion = $this->heroDominion();
        $battle = app(HeroBattleService::class)->createEncounterBattle('lich_king', [$dominion], 'practice');
        $player = $battle->combatants()->where('dominion_id', $dominion->id)->firstOrFail();
        $player->update(['automated' => false]);

        $this->get(route('dominion.heroes.battles'))
            ->assertOk()
            ->assertSee('Your side')
            ->assertSee('Opponents')
            ->assertSee('Tome of Power')
            ->assertSee('Enrage')
            ->assertSee('Power Source')
            ->assertSee('data-action="attack"', false)
            ->assertSee('data-action="recover"', false)
            ->assertSeeInOrder(['Your side', 'Opponents', 'Turn 1 orders', 'Lock in turn 1'])
            ->assertDontSee('Queued:');
    }

    public function testActiveBattlesSpanThePageAboveHistoryAndInformation(): void
    {
        $this->heroDominion();

        $content = $this->get(route('dominion.heroes.battles'))->assertOk()->getContent();

        $active = strpos($content, 'Active Battles</span>');
        $previous = strpos($content, 'Previous Battles</span>');
        $information = strpos($content, '<span class="card-title">How Hero Battles Work</span>');

        $this->assertNotFalse($active);
        $this->assertLessThan($previous, $active);
        $this->assertLessThan($information, $previous);
        $this->assertLessThan($active, strpos($content, '<div class="col-12">'), 'Active Battles has the whole row');
    }

    public function testBattleButtonsStayInTheActiveBattlesHeader(): void
    {
        $this->heroDominion();

        $content = $this->get(route('dominion.heroes.battles'))->assertOk()->getContent();

        $header = substr($content, strpos($content, 'Active Battles</span>'));
        $header = substr($header, 0, strpos($header, 'class="card-body"'));

        $this->assertStringContainsString(route('dominion.heroes.battles.practice'), $header);
        $this->assertStringContainsString(route('dominion.heroes.battles.queue'), $header);
    }

    public function testBattleButtonsAreHiddenDuringABattle(): void
    {
        $dominion = $this->heroDominion();
        app(HeroBattleService::class)->createEncounterBattle('heart_of_ice', [$dominion], 'practice');

        $this->get(route('dominion.heroes.battles'))
            ->assertOk()
            ->assertDontSee(route('dominion.heroes.battles.practice'), false)
            ->assertDontSee(route('dominion.heroes.battles.queue'), false);
    }

    public function testBattlePageShowsTheHerosTechniques(): void
    {
        $dominion = $this->heroDominion();
        $dominion->hero->update(['experience' => 3500, 'loadout' => ['technique' => 'shield_bash']]);
        app(HeroBattleService::class)->createEncounterBattle('heart_of_ice', [$dominion->fresh()], 'practice');

        $this->get(route('dominion.heroes.battles'))
            ->assertOk()
            ->assertSee('Shield Bash');
    }

    public function testEquippedClassAbilitiesJoinTheHerosActions(): void
    {
        $dominion = $this->heroDominion();
        $dominion->hero->update([
            'class_data' => ['infiltrator' => ['key' => 'infiltrator', 'experience' => 2300]],
            'loadout' => ['ability' => 'shadow_strike'],
        ]);
        $battle = app(HeroBattleService::class)->createEncounterBattle('heart_of_ice', [$dominion->fresh()], 'practice');
        $player = $battle->combatants()->where('dominion_id', $dominion->id)->firstOrFail();

        $this->assertContains('shadow_strike', $player->abilities);
        $this->get(route('dominion.heroes.battles'))
            ->assertOk()
            ->assertSee('Shadow Strike')
            ->assertSee('data-action="shadow_strike"', false);
    }

    public function testCoopBattleQueuesAheadAndOffersToGuardTheAlly(): void
    {
        $dominion = $this->heroDominion();
        $ally = $this->createDominionWithLegacyStats($this->createUser(), $dominion->round, Race::where('name', 'Human')->firstOrFail());
        Hero::create([
            'dominion_id' => $ally->id,
            'name' => 'Ally Hero',
            'class' => 'blacksmith',
            'experience' => 2000,
            'class_data' => [],
        ]);
        $battle = app(HeroBattleService::class)->createEncounterBattle('grand_magister', [$dominion, $ally->refresh()], 'practice');
        $battle->combatants()->whereNotNull('hero_id')->update(['automated' => false]);
        $player = $battle->combatants()->where('dominion_id', $dominion->id)->firstOrFail();

        $this->get(route('dominion.heroes.battles'))
            ->assertOk()
            ->assertSee('Queue turn 1')
            ->assertSee('data-waiting-on-you', false)
            ->assertSee('Guard Ally Hero')
            ->assertSee('Heal Ally Hero');

        $this->lockIn($battle, $player, 'defend');
        $this->assertEquals(1, $battle->fresh()->current_turn, 'The turn waits for the other player');

        $this->get(route('dominion.heroes.battles'))
            ->assertOk()
            ->assertSee('Queue turn 2')
            ->assertSee('Queued:')
            ->assertSee('Waiting for Ally Hero')
            ->assertDontSee('data-waiting-on-you', false)
            ->assertSeeInOrder(['Plan', 'T1', 'Defend']);
    }

    public function testHeroOnAutopilotOffersToCancelIt(): void
    {
        $dominion = $this->heroDominion();
        $battle = app(HeroBattleService::class)->createEncounterBattle('heart_of_ice', [$dominion], 'practice');
        $battle->combatants()->where('dominion_id', $dominion->id)->update(['automated' => true]);

        $this->get(route('dominion.heroes.battles'))
            ->assertOk()
            ->assertSee('Page Hero is on autopilot')
            ->assertSee('Cancel autopilot')
            ->assertDontSee('data-action="attack"', false);
    }

    public function testQueueingAnActionThroughTheRouteResolvesTheTurn(): void
    {
        $dominion = $this->heroDominion();
        $battle = app(HeroBattleService::class)->createEncounterBattle('heart_of_ice', [$dominion], 'practice');
        $player = $battle->combatants()->where('dominion_id', $dominion->id)->firstOrFail();

        $this->lockIn($battle, $player, 'defend');

        $this->assertEquals(2, $battle->fresh()->current_turn);

        $this->get(route('dominion.heroes.battles'))
            ->assertOk()
            ->assertSee('takes a defensive stance');
    }

    public function testBattleReportRendersFinishedTeamBattle(): void
    {
        $dominion = $this->heroDominion();
        $battle = app(HeroBattleService::class)->createEncounterBattle('admiral_varos', [$dominion], 'practice');
        $battle->combatants()->update(['automated' => true]);
        app(HeroBattleService::class)->processTurn($battle->fresh());

        $this->get(route('dominion.heroes.battles.report', ['battle' => $battle->id]))
            ->assertOk()
            ->assertSee('Admiral Varos')
            ->assertSee("Turn {$battle->fresh()->current_turn} results")
            ->assertSee('Earlier turns');
    }

    public function testBattlePageSummarisesTheLastTurnFromTheViewersSide(): void
    {
        $dominion = $this->heroDominion();
        $battle = app(HeroBattleService::class)->createEncounterBattle('heart_of_ice', [$dominion], 'practice');
        $player = $battle->combatants()->where('dominion_id', $dominion->id)->firstOrFail();

        $this->lockIn($battle, $player, 'defend');

        $content = $this->get(route('dominion.heroes.battles'))->assertOk()->getContent();

        $this->assertStringContainsString('Turn 1 results', $content);
        $this->assertStringContainsString('<span class="turn-name-ally">Page Hero</span>: Defend', $content);
        $this->assertMatchesRegularExpression('/<span class="turn-name-enemy">[^<]+<\/span>: /', $content);
        $this->assertStringContainsString('<span class="turn-name-ally">Page Hero</span> takes a defensive stance.', $content);
        $this->assertStringNotContainsString('<div class="turn-phase-name">Defeats</div>', $content, 'Nobody fell on turn 1');
    }

    /**
     * Splits the heroes page header into its Dominion and Hero Battle sections.
     *
     * @return array{dominion: string, heroBattle: string}
     */
    protected function heroSections(string $content): array
    {
        $dominionStart = strpos($content, 'ra-tower"></i> Dominion Bonuses');
        $heroBattleStart = strpos($content, 'ra-axe"></i> Hero Battles');
        $upgradesStart = strpos($content, 'id="hero-upgrades"');
        $this->assertNotFalse($dominionStart);
        $this->assertNotFalse($heroBattleStart);
        $this->assertNotFalse($upgradesStart);

        return [
            'dominion' => substr($content, $dominionStart, $heroBattleStart - $dominionStart),
            'heroBattle' => substr($content, $heroBattleStart, $upgradesStart - $heroBattleStart),
        ];
    }

    /**
     * The loadout form's table row for one option.
     */
    protected function loadoutRow(string $loadout, string $key): string
    {
        $start = strpos($loadout, "id=\"loadout_{$key}\"");
        $this->assertNotFalse($start, "{$key} is in the loadout form");

        return substr($loadout, $start, strpos($loadout, '</tr>', $start) - $start);
    }

    public function testHeroesPageShowsTheHeroBannerWithLevelProgress(): void
    {
        $this->heroDominion();

        $this->get(route('dominion.heroes'))
            ->assertOk()
            ->assertSee('Page Hero')
            ->assertSee('Level 4 Blacksmith')
            ->assertSee('2,000 / 2,300 XP · 300 to level 5');
    }

    public function testHeroesPageSplitsUpgradesFromTheLoadout(): void
    {
        $dominion = $this->heroDominion();
        $dominion->hero->update(['experience' => 2300, 'loadout' => ['technique' => 'backstab']]);

        $sections = $this->heroSections($this->get(route('dominion.heroes'))->assertOk()->getContent());

        $this->assertStringContainsString('Lv 1 · Doctrine', $sections['dominion']);
        $this->assertStringContainsString('Lv 2 · Item or Magic', $sections['dominion']);
        $this->assertStringContainsString('Choose upgrade', $sections['dominion']);
        $this->assertStringContainsString('<a href="#hero-upgrades" class="hero-tile hero-tile-open" data-upgrade-level="2">', $sections['dominion']);
        $this->assertStringContainsString('Reach level 6', $sections['dominion']);
        $this->assertStringNotContainsString('Technique', $sections['dominion']);

        $this->assertStringContainsString('Backstab', $sections['heroBattle']);
        $this->assertStringContainsString('against a target that was Focused at the start of the turn', $sections['heroBattle']);
        $this->assertStringContainsString('<a href="#hero-loadout" class="hero-tile hero-tile-open" data-loadout-slot="ability">', $sections['heroBattle']);
        $this->assertStringContainsString('<a href="#hero-loadout" class="hero-tile hero-tile-open" data-loadout-slot="gear">', $sections['heroBattle'], 'The first gear slot is open');
        $this->assertSame(1, substr_count($sections['heroBattle'], 'Reach level 7'), 'The second gear slot opens later');
        $this->assertStringNotContainsString('Choose upgrade', $sections['heroBattle']);
    }

    public function testHeroesPageShowsRatingAndGearStatChanges(): void
    {
        $dominion = $this->heroDominion();
        $dominion->hero->forceFill(['experience' => 5000, 'loadout' => ['gear' => ['greatsword']], 'combat_rating' => 1043, 'stat_combat_wins' => 7, 'stat_combat_losses' => 4, 'stat_combat_draws' => 1])->save();

        $heroBattle = $this->heroSections($this->get(route('dominion.heroes'))->assertOk()->getContent())['heroBattle'];

        $this->assertMatchesRegularExpression('/Rating<\/div>\s*<div class="hero-stat-value">1043<\/div>\s*<div class="hero-stat-change">7-4-1<\/div>/', $heroBattle);
        $this->assertMatchesRegularExpression('/Attack<\/div>\s*<div class="hero-stat-value">42<\/div>\s*<div class="hero-stat-change text-green">\+2<\/div>/', $heroBattle);
        $this->assertMatchesRegularExpression('/Evasion<\/div>\s*<div class="hero-stat-value">0<\/div>\s*<div class="hero-stat-change text-red">-10<\/div>/', $heroBattle);
        $this->assertMatchesRegularExpression('/Defense<\/div>\s*<div class="hero-stat-value">20<\/div>\s*<div class="hero-stat-change"><\/div>/', $heroBattle);
    }

    public function testHeroesPageListsPlayedClassesBeforeUnplayedOnes(): void
    {
        $dominion = $this->heroDominion();
        $dominion->hero->update(['class_data' => [
            'architect' => [
                'key' => 'architect',
                'level' => 3,
                'experience' => 1200,
                'perk_type' => 'construction_cost',
                'bonus' => -1.875,
            ],
        ]]);

        $dominionSection = $this->heroSections($this->get(route('dominion.heroes'))->assertOk()->getContent())['dominion'];
        $unplayedStart = strpos($dominionSection, 'id="hero-unplayed-classes"');
        $this->assertNotFalse($unplayedStart);
        $played = substr($dominionSection, 0, $unplayedStart);
        $unplayed = substr($dominionSection, $unplayedStart);

        $this->assertMatchesRegularExpression('/Blacksmith<\/div>.*?Lv 4<\/span>\s*<span class="hero-class-active">Active<\/span>/s', $played);
        $this->assertMatchesRegularExpression('/Architect<\/div>.*?\(halved\).*?Lv 3<\/span>/s', $played);
        $this->assertStringContainsString(route('dominion.heroes.change-class', 'architect'), $played);
        $this->assertStringContainsString('Farmer', $unplayed);
        $this->assertStringNotContainsString('Farmer', $played);

        $unplayedCount = count(app(\OpenDominion\Helpers\HeroHelper::class)->getClasses()) - 2;
        $this->assertStringContainsString("Show {$unplayedCount} more classes", $unplayed);
    }

    public function testHeroesPageHidesTheShrineBonusWithoutShrines(): void
    {
        $this->heroDominion();

        $this->get(route('dominion.heroes'))
            ->assertOk()
            ->assertDontSee('Your hero bonuses are being increased by')
            ->assertDontSee('Before your hero bonus increase');
    }

    public function testHeroesPageShowsTheShrineBonusWithShrines(): void
    {
        $dominion = $this->heroDominion();
        $dominion->update(['building_shrine' => 5]);

        $dominionSection = $this->heroSections($this->get(route('dominion.heroes'))->assertOk()->getContent())['dominion'];
        $this->assertMatchesRegularExpression('/Your hero bonuses are being increased by \d+\.\d{2}%/', $dominionSection);
        $this->assertMatchesRegularExpression('/-\d+\.\d{2}% military training cost<\/span>\s*<span class="hero-class-note"[^>]*>\(-0\.80%\)<\/span>/', $dominionSection);
    }

    public function testUpgradeAndLoadoutFormsAreSeparate(): void
    {
        $this->heroDominion();

        $content = $this->get(route('dominion.heroes'))
            ->assertOk()
            ->assertDontSee('<th>Effect</th>', false)
            ->assertDontSee('Combat Upgrades')
            ->getContent();

        $upgradesStart = strpos($content, 'id="hero-upgrades"');
        $upgrades = substr($content, $upgradesStart, strpos($content, '</form>', $upgradesStart) - $upgradesStart);
        $loadoutStart = strpos($content, 'id="hero-loadout"');
        $loadout = substr($content, $loadoutStart, strpos($content, '</form>', $loadoutStart) - $loadoutStart);

        $this->assertLessThan($loadoutStart, $upgradesStart, 'Combat Loadout sits beneath Hero Upgrades');
        $this->assertStringContainsString('ra-tower me-1"></i> Hero Upgrades', $upgrades);
        $this->assertStringContainsString('Experience gains from invasion and exploration are doubled', $upgrades);
        $this->assertStringContainsString('-1 wizard strength cost of self spells', $upgrades);
        $this->assertStringNotContainsString('Backstab', $upgrades);
        $this->assertStringNotContainsString('Greatsword', $upgrades);

        $this->assertStringContainsString('ra-axe me-1"></i> Combat Loadout', $loadout);
        $this->assertStringContainsString('fa-info-circle', $this->loadoutRow($loadout, 'backstab'));
        $this->assertStringContainsString('+2 Attack, -10 Evasion', $loadout);
        $this->assertStringContainsString('Being attacked while Defending Staggers the attacker', $loadout);
        $this->assertStringContainsString('fa-info-circle', $this->loadoutRow($loadout, 'shadow_strike'), 'Reviewed abilities keep their rules in a tooltip');
        $this->assertStringNotContainsString('wizard strength', $loadout);
    }

    public function testChosenTilesKeepTheFullDescriptionInATooltip(): void
    {
        $dominion = $this->heroDominion();
        $dominion->hero->update(['experience' => 2900, 'loadout' => ['technique' => 'backstab']]);
        \OpenDominion\Models\HeroHeroUpgrade::create([
            'hero_id' => $dominion->hero->id,
            'hero_upgrade_id' => \OpenDominion\Models\HeroUpgrade::where('key', 'evocation')->firstOrFail()->id,
        ]);

        $sections = $this->heroSections($this->get(route('dominion.heroes'))->assertOk()->getContent());

        $this->assertStringContainsString('<div class="hero-tile" title="+10% fireball damage" data-bs-toggle="tooltip" tabindex="0">', $sections['dominion']);
        $this->assertStringContainsString('<div class="hero-tile" title="Your attacks deal +' . Backstab::BONUS_DAMAGE . ' damage', $sections['heroBattle']);
    }

    public function testHeroStatsSitBeneathTheLoadoutTiles(): void
    {
        $this->heroDominion();

        $heroBattle = $this->heroSections($this->get(route('dominion.heroes'))->assertOk()->getContent())['heroBattle'];

        $this->assertLessThan(strpos($heroBattle, 'class="hero-stats"'), strpos($heroBattle, 'class="hero-tiles"'));
    }

    public function testLoadoutFormShowsWhatIsEquippedAndWhatIsLocked(): void
    {
        $dominion = $this->heroDominion();
        $dominion->hero->update(['experience' => 1200, 'loadout' => ['technique' => 'backstab']]);

        $content = $this->get(route('dominion.heroes'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/id="loadout_backstab"\s+value="backstab"\s+checked/', $content);
        $this->assertMatchesRegularExpression('/id="loadout_riposte"\s+value="riposte"\s+disabled data-locked/', $content);
        $this->assertMatchesRegularExpression('/name="gear\[\]"\s+id="loadout_greatsword"\s+value="greatsword"\s*>/', $content);
        $this->assertStringContainsString('<small class="text-muted">Requires level 7</small>', $content);
    }

    public function testClassOptionsKeepTheirRequirementOnceMet(): void
    {
        $dominion = $this->heroDominion();
        $dominion->hero->update(['experience' => 2300]);
        $heroHelper = app(\OpenDominion\Helpers\HeroHelper::class);
        $registry = app(\OpenDominion\HeroCombat\Registry\CombatRegistry::class);

        $content = $this->get(route('dominion.heroes'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/id="loadout_forge"\s+value="forge"\s*>/', $content, 'Forge is available');
        $this->assertStringContainsString($heroHelper->getLoadoutOptionRequirement($registry->loadoutOption('forge')), $this->loadoutRow($content, 'forge'));
        $this->assertStringNotContainsString('text-muted', $this->loadoutRow($content, 'backstab'), 'A met level requirement is hidden');
    }

    public function testEmptySlotTilesOnlyHighlightOptionsTheHeroCanEquip(): void
    {
        $dominion = $this->heroDominion();
        $dominion->hero->update(['experience' => 2300]);

        $content = $this->get(route('dominion.heroes'))->assertOk()->getContent();

        $this->assertSame(3, preg_match_all('/<tr class="hero-loadout-option[^"]*" data-slot="ability" data-available>/', $content), 'The Blacksmith signature, Blade Flurry and Whirlwind');
        $this->assertSame(5, preg_match_all('/<tr class="hero-loadout-option[^"]*" data-slot="technique" data-available>/', $content), 'Technique I only');
        $this->assertSame(6, preg_match_all('/<tr class="hero-loadout-option[^"]*" data-slot="gear" data-available>/', $content));
        $this->assertStringContainsString(".hero-loadout-option[data-available][data-slot=", $content);
    }

    public function testSavingTheLoadoutThroughTheRoute(): void
    {
        $dominion = $this->heroDominion();
        $dominion->hero->update(['experience' => 10000]);

        $this->post(route('dominion.heroes.loadout'), [
            'technique' => 'riposte',
            'ability' => '',
            'gear' => ['greatsword', 'hooded_cloak'],
        ])->assertRedirect(route('dominion.heroes'));

        $this->assertEquals(
            ['technique' => 'riposte', 'ability' => null, 'gear' => ['greatsword', 'hooded_cloak']],
            $dominion->hero->fresh()->loadout
        );
    }

    public function testSavingAnInvalidLoadoutShowsAnError(): void
    {
        $dominion = $this->heroDominion();

        $this->from(route('dominion.heroes'))
            ->post(route('dominion.heroes.loadout'), ['technique' => 'riposte'])
            ->assertRedirect(route('dominion.heroes'))
            ->assertSessionHasErrors();

        $this->assertNull($dominion->hero->fresh()->loadout);
    }

    public function testSidebarBadgeCountsUpgradesAndEmptyLoadoutSlots(): void
    {
        $dominion = $this->heroDominion();
        $dominion->hero->update(['experience' => 1200]);

        $this->assertMatchesRegularExpression(
            '/Heroes\s*<span class="badge text-bg-primary">4<\/span>/',
            $this->get(route('dominion.heroes'))->assertOk()->getContent(),
            'Doctrine, level 2 upgrade, the technique slot and the first gear slot'
        );
    }

    public function testSidebarBadgeSkipsFilledLoadoutSlots(): void
    {
        $dominion = $this->heroDominion();
        $dominion->hero->update(['experience' => 1200, 'loadout' => ['technique' => 'backstab']]);

        $this->assertMatchesRegularExpression(
            '/Heroes\s*<span class="badge text-bg-primary">3<\/span>/',
            $this->get(route('dominion.heroes'))->assertOk()->getContent()
        );
    }

    public function testScribesListCombatLoadoutOptions(): void
    {
        $this->createAndImpersonateUser();

        $this->get(route('scribes.heroes'))
            ->assertOk()
            ->assertSee('Combat Loadout')
            ->assertSee('Vestments of Renewal')
            ->assertSee('Infiltrator level 5');
    }
}

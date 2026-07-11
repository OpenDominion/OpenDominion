<?php

namespace OpenDominion\Tests\Http;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\NewPlayerTourService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class NewPlayerTourTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    public function testExistingUserWithoutTourStateKeepsCompleteNavigation(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $this->visitRoute('dominion.status')
            ->dontSeeElement('[data-new-player-tour]')
            ->seeElement('[data-onboarding-nav="status"]')
            ->seeElement('[data-onboarding-nav="advisors"]')
            ->seeElement('[data-onboarding-nav="explore"]')
            ->seeElement('[data-onboarding-nav="military"]')
            ->seeElement('[data-onboarding-nav="magic"]')
            ->seeElement('[data-onboarding-nav="realm"]')
            ->seeElement('[data-onboarding-nav="rankings"]');
    }

    public function testActiveTourStartsWithOnlyStatusRevealed(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => app(NewPlayerTourService::class)->defaultState()],
        ]);
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $this->visitRoute('dominion.status')
            ->seeElement('[data-new-player-tour]')
            ->seeElement('[data-onboarding-nav="status"]')
            ->dontSeeElement('[data-onboarding-nav="advisors"]')
            ->dontSeeElement('[data-onboarding-nav="explore"]')
            ->dontSeeElement('[data-onboarding-nav="military"]');
    }

    public function testCompletingStatusMovesToQuickStartBeforeAdvisors(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => app(NewPlayerTourService::class)->defaultState()],
        ]);
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $this->visitRoute('dominion.status')
            ->press('Continue')
            ->seeRouteIs('dominion.status')
            ->see('Choose your guided start')
            ->dontSeeElement('[data-onboarding-nav="advisors"]')
            ->dontSeeElement('[data-onboarding-nav="daily_bonus"]');

        $this->assertSame('quick_start', $user->fresh()->getSetting(NewPlayerTourService::SETTING_KEY)['step']);
    }

    public function testStartingBuildingsStageRevealsRezoneButNotOtherDominionPages(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'starting_buildings']],
        ]);
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $this->visitRoute('dominion.protection.buildings')
            ->seeElement('[data-onboarding-nav="construction"]')
            ->seeElement('a[href="' . route('dominion.rezone') . '"]')
            ->dontSeeElement('a[href="' . route('dominion.improvements') . '"]')
            ->dontSeeElement('[data-onboarding-nav="military"]');
    }

    public function testVerifiedQuestSequenceRequiresRecordedGameActions(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'starting_buildings']],
        ]);
        $round = $this->createRound();
        $dominion = $this->createAndSelectDominion($user, $round);
        $tour = app(NewPlayerTourService::class);

        $this->assertFalse($tour->advance($user, $dominion));
        $dominion->history()->create(['event' => HistoryService::EVENT_TICK, 'delta' => ['action' => 'starting_buildings']]);
        $this->assertFalse($tour->advance($user, $dominion));
        $dominion->update(['protection_ticks_remaining' => $dominion->protection_ticks]);
        $this->assertTrue($tour->advance($user, $dominion));
        $this->assertSame('daily_bonus', $user->getSetting(NewPlayerTourService::SETTING_KEY)['step']);

        $this->assertFalse($tour->advance($user, $dominion));
        $dominion->history()->create(['event' => HistoryService::EVENT_ACTION_DAILY_BONUS, 'delta' => ['daily_land' => true]]);
        $dominion->history()->create(['event' => HistoryService::EVENT_ACTION_DAILY_BONUS, 'delta' => ['daily_platinum' => true]]);
        $this->assertTrue($tour->advance($user, $dominion));
        $this->assertSame('discord', $user->getSetting(NewPlayerTourService::SETTING_KEY)['step']);

        $this->assertTrue($tour->advance($user, $dominion));
        $this->assertSame('explore', $user->getSetting(NewPlayerTourService::SETTING_KEY)['step']);

        $this->assertFalse($tour->advance($user, $dominion));
        $dominion->history()->create(['event' => HistoryService::EVENT_ACTION_EXPLORE, 'delta' => ['queue' => ['exploration' => ['land_plain' => 1]]]]);
        $this->assertFalse($tour->advance($user, $dominion));
        $dominion->history()->create(['event' => HistoryService::EVENT_ACTION_PROTECTION_ADVANCE_TICK, 'delta' => []]);
        $this->assertTrue($tour->advance($user, $dominion));
        $this->assertSame('construction', $user->getSetting(NewPlayerTourService::SETTING_KEY)['step']);

        $this->assertFalse($tour->advance($user, $dominion));
        $dominion->history()->create(['event' => HistoryService::EVENT_ACTION_CONSTRUCT, 'delta' => ['queue' => ['construction' => ['building_home' => 1]]]]);
        $this->assertFalse($tour->advance($user, $dominion));
        $dominion->history()->create(['event' => HistoryService::EVENT_ACTION_PROTECTION_ADVANCE_TICK, 'delta' => []]);
        $this->assertTrue($tour->advance($user, $dominion));
        $this->assertSame('military', $user->getSetting(NewPlayerTourService::SETTING_KEY)['step']);

        $this->assertFalse($tour->advance($user, $dominion));
        $dominion->history()->create(['event' => HistoryService::EVENT_ACTION_TRAIN, 'delta' => ['queue' => ['training' => ['military_unit1' => 1]]]]);
        $this->assertTrue($tour->advance($user, $dominion));
        $this->assertSame('magic', $user->getSetting(NewPlayerTourService::SETTING_KEY)['step']);

        $this->assertFalse($tour->advance($user, $dominion));
        $dominion->history()->create(['event' => HistoryService::EVENT_ACTION_CAST_SPELL, 'delta' => ['queue' => ['active_spells' => ['midas_touch' => 12]]]]);
        $this->assertTrue($tour->advance($user, $dominion));
        $this->assertSame('realm', $user->getSetting(NewPlayerTourService::SETTING_KEY)['step']);
    }

    public function testNewAccountQuickStartStepRequiresQuickProtection(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'quick_start', 'new_account' => true]],
        ]);
        $round = $this->createRound();
        $dominion = $this->createAndSelectDominion($user, $round);
        $tour = app(NewPlayerTourService::class);

        $this->visitRoute('dominion.status')
            ->see('Choose your guided start')
            ->see('Open Restart & Rename')
            ->see('choose your race and select Quick Start');

        $this->assertFalse($tour->advance($user, $dominion));

        $this->visitRoute('dominion.misc.restart')
            ->seeElement('[data-onboarding-target="quick-start"]')
            ->see('Your race defines your units, perks, spells, and strategic strengths.');

        $dominion->history()->create([
            'event' => HistoryService::EVENT_ACTION_RESTART,
            'delta' => ['action' => 'quick'],
        ]);
        $this->assertTrue($tour->advance($user, $dominion));
        $this->assertSame('advisors', $user->getSetting(NewPlayerTourService::SETTING_KEY)['step']);

        $this->assertTrue($tour->advance($user, $dominion));
        $this->assertSame('starting_buildings', $user->getSetting(NewPlayerTourService::SETTING_KEY)['step']);
    }

    public function testRestartedGuideDoesNotRequireDominionRestart(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'quick_start', 'new_account' => false]],
        ]);
        $round = $this->createRound();
        $dominion = $this->createAndSelectDominion($user, $round);

        $this->visitRoute('dominion.status')
            ->see('Quick Start for new dominions')
            ->see('continue without resetting your current dominion')
            ->dontSee('Open Restart & Rename')
            ->press('Continue');

        $this->assertSame('advisors', $user->fresh()->getSetting(NewPlayerTourService::SETTING_KEY)['step']);
        $this->assertNotSame('quick', $dominion->fresh()->protection_type);
    }

    public function testCommunityLessonIncludesDirectDiscordInvite(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'discord']],
        ]);
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $this->visitRoute('dominion.bonuses')
            ->see('Join the OpenDominion Discord')
            ->seeLink('Join Discord', 'https://discord.gg/mFk2wZT')
            ->seeElement('a[href="https://discord.gg/mFk2wZT"][target="_blank"]');
    }

    public function testOutstandingQuestShowsObjectiveWithoutContinue(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'explore']],
        ]);
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $this->visitRoute('dominion.explore')
            ->see('Your objective')
            ->see('Send at least one acre on exploration.')
            ->dontSeeElement('form[action="' . route('new-player-tour.advance') . '"]');
    }

    public function testConstructionAfterExploreIsSeparateVerifiedQuest(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'construction']],
        ]);
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $this->visitRoute('dominion.construct')
            ->see('Queue your next buildings')
            ->see('Submit at least one construction order.')
            ->seeElement('[data-onboarding-target="construction"]')
            ->dontSeeElement('form[action="' . route('new-player-tour.advance') . '"]');
    }

    public function testSavedStartingBuildingsRequireProtectionConfirmation(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'starting_buildings']],
        ]);
        $round = $this->createRound();
        $dominion = $this->createAndSelectDominion($user, $round);
        $dominion->history()->create([
            'event' => HistoryService::EVENT_TICK,
            'delta' => ['action' => 'starting_buildings'],
        ]);

        $this->visitRoute('dominion.protection.buildings')
            ->see('Confirm your opening')
            ->see('Press Next » in the protection banner')
            ->seeElement('[data-onboarding-target="protection-confirm"]')
            ->dontSeeElement('form[action="' . route('new-player-tour.advance') . '"]');
    }

    public function testSatisfiedQuestShowsCompletionAndContinueAction(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'explore']],
        ]);
        $round = $this->createRound();
        $dominion = $this->createAndSelectDominion($user, $round);
        $dominion->history()->create([
            'event' => HistoryService::EVENT_ACTION_EXPLORE,
            'delta' => ['queue' => ['exploration' => ['land_plain' => 1]]],
        ]);
        $dominion->history()->create([
            'event' => HistoryService::EVENT_ACTION_PROTECTION_ADVANCE_TICK,
            'delta' => [],
        ]);

        $this->visitRoute('dominion.explore')
            ->see('Quest complete')
            ->see('Continue quest')
            ->seeElement('form[action="' . route('new-player-tour.advance') . '"]');
    }

    public function testQueuedExploreRequiresProtectionAdvanceBeforeContinue(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'explore']],
        ]);
        $round = $this->createRound();
        $dominion = $this->createAndSelectDominion($user, $round);
        $dominion->history()->create([
            'event' => HistoryService::EVENT_ACTION_EXPLORE,
            'delta' => ['queue' => ['exploration' => ['land_plain' => 1]]],
        ]);

        $this->visitRoute('dominion.explore')
            ->see('Bring your explored acres home')
            ->see('Press Next » in the protection banner')
            ->seeElement('[data-onboarding-target="protection-confirm"]')
            ->dontSeeElement('form[action="' . route('new-player-tour.advance') . '"]');
    }

    public function testQueuedConstructionRequiresProtectionAdvanceBeforeContinue(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'construction']],
        ]);
        $round = $this->createRound();
        $dominion = $this->createAndSelectDominion($user, $round);
        $dominion->history()->create([
            'event' => HistoryService::EVENT_ACTION_CONSTRUCT,
            'delta' => ['queue' => ['construction' => ['building_home' => 1]]],
        ]);

        $this->visitRoute('dominion.construct')
            ->see('Finish your construction')
            ->see('ready for military training')
            ->seeElement('[data-onboarding-target="protection-confirm"]')
            ->dontSeeElement('form[action="' . route('new-player-tour.advance') . '"]');
    }

    public function testExploreAndConstructionNeedOnlyTheirActionsAfterProtection(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'explore', 'new_account' => false]],
        ]);
        $round = $this->createRound('-5 days');
        $dominion = $this->createAndSelectDominion($user, $round);
        $dominion->update(['protection_finished' => true]);
        $tour = app(NewPlayerTourService::class);

        $dominion->history()->create([
            'event' => HistoryService::EVENT_ACTION_EXPLORE,
            'delta' => ['queue' => ['exploration' => ['land_plain' => 1]]],
        ]);
        $this->assertTrue($tour->advance($user, $dominion));
        $this->assertSame('construction', $user->fresh()->getSetting(NewPlayerTourService::SETTING_KEY)['step']);

        $dominion->history()->create([
            'event' => HistoryService::EVENT_ACTION_CONSTRUCT,
            'delta' => ['queue' => ['construction' => ['building_home' => 1]]],
        ]);
        $this->assertTrue($tour->advance($user, $dominion));
        $this->assertSame('military', $user->fresh()->getSetting(NewPlayerTourService::SETTING_KEY)['step']);
    }

    public function testDailyBonusRequiresOneClaimOfEachType(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'daily_bonus']],
        ]);
        $round = $this->createRound();
        $dominion = $this->createAndSelectDominion($user, $round);
        $tour = app(NewPlayerTourService::class);

        $dominion->history()->create(['event' => HistoryService::EVENT_ACTION_DAILY_BONUS, 'delta' => ['daily_land' => true]]);
        $dominion->history()->create(['event' => HistoryService::EVENT_ACTION_DAILY_BONUS, 'delta' => ['daily_land' => true]]);
        $this->assertFalse($tour->advance($user, $dominion));

        $dominion->history()->create(['event' => HistoryService::EVENT_ACTION_DAILY_BONUS, 'delta' => ['daily_platinum' => true]]);
        $this->assertTrue($tour->advance($user, $dominion));
        $this->assertSame('discord', $user->fresh()->getSetting(NewPlayerTourService::SETTING_KEY)['step']);
    }

    public function testMagicLessonLeadsToProtectionCheckpointBeforeRealmLesson(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'magic']],
        ]);
        $round = $this->createRound();
        $dominion = $this->createAndSelectDominion($user, $round);
        $dominion->history()->create([
            'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
            'delta' => ['queue' => ['active_spells' => ['midas_touch' => 12]]],
        ]);

        $this->visitRoute('dominion.magic')
            ->press('Continue quest')
            ->seeRouteIs('dominion.magic')
            ->see('Protection chapter complete')
            ->see('Keep training troops and complete your remaining protection hours')
            ->see('ask questions in Discord')
            ->see('The guide will resume with Realms and Rankings')
            ->see('Restart or Rename')
            ->dontSeeElement('form[action="' . route('new-player-tour.advance') . '"]')
            ->seeElement('[data-onboarding-nav="realm"]')
            ->seeElement('[data-onboarding-nav="rankings"]');

        $this->assertSame('realm', $user->fresh()->getSetting(NewPlayerTourService::SETTING_KEY)['step']);
    }

    public function testPausedActiveLessonCannotAdvanceWhileUnderProtection(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'realm']],
        ]);
        $round = $this->createRound();
        $dominion = $this->createAndSelectDominion($user, $round);

        app(NewPlayerTourService::class)->advance($user, $dominion);

        $this->assertSame('realm', $user->fresh()->getSetting(NewPlayerTourService::SETTING_KEY)['step']);
    }

    public function testRealmLessonResumesAfterProtectionAndRealmAssignment(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'realm']],
        ]);
        $round = $this->createRound('-5 days');
        $round->update(['assignment_complete' => true]);
        $dominion = $this->createAndSelectDominion($user, $round);
        $dominion->update(['protection_finished' => true]);

        $this->visitRoute('dominion.realm')
            ->see('Meet your team')
            ->seeElement('[data-new-player-tour]')
            ->seeElement('[data-onboarding-nav="realm"]')
            ->dontSeeElement('[data-onboarding-nav="rankings"]');
    }

    public function testSkipRevealsEverythingAndPreservesSkippedState(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => app(NewPlayerTourService::class)->defaultState()],
        ]);
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $this->visitRoute('dominion.status')
            ->press('Skip guide')
            ->seeElement('[data-onboarding-nav="advisors"]')
            ->seeElement('[data-onboarding-nav="rankings"]')
            ->dontSeeElement('[data-new-player-tour]');

        $this->assertSame('skipped', $user->fresh()->getSetting(NewPlayerTourService::SETTING_KEY)['status']);
    }

    public function testAnyUserCanRestartGuideFromSettings(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'completed', 'step' => 'rankings']],
        ]);
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $this->visitRoute('settings')
            ->press('Restart Game Guide')
            ->seeRouteIs('dominion.status')
            ->seeElement('[data-new-player-tour]');

        $this->assertSame(
            ['status' => 'active', 'step' => 'status', 'new_account' => false],
            $user->fresh()->getSetting(NewPlayerTourService::SETTING_KEY)
        );
    }

    public function testFinishingGuideStaysOnRankingsAndRevealsFullNavigation(): void
    {
        $user = $this->createAndImpersonateUser(null, [
            'settings' => [NewPlayerTourService::SETTING_KEY => ['status' => 'active', 'step' => 'rankings']],
        ]);
        $round = $this->createRound('-5 days');
        $round->update(['assignment_complete' => true]);
        $dominion = $this->createAndSelectDominion($user, $round);
        $dominion->update(['protection_finished' => true]);

        $this->visitRoute('dominion.rankings')
            ->press('Finish guide')
            ->seeRouteIs('dominion.rankings', ['largest-dominions'])
            ->dontSeeElement('[data-new-player-tour]')
            ->seeElement('[data-onboarding-nav="status"]')
            ->seeElement('[data-onboarding-nav="rankings"]');

        $this->assertSame('completed', $user->fresh()->getSetting(NewPlayerTourService::SETTING_KEY)['status']);
    }
}

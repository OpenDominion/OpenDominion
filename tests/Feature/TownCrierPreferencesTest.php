<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Http\Request;
use OpenDominion\Http\Controllers\Dominion\TownCrierController;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Services\GameEventService;
use OpenDominion\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TownCrierPreferencesTest extends AbstractTestCase
{
    public function testPreferenceRequiresAuthentication(): void
    {
        $this->post('/dominion/town-crier', ['show_bot_attacks' => '1'])
            ->assertRedirect('/auth/login');
    }

    public function testPreferenceRequiresAnAvailableDominion(): void
    {
        $this->createAndImpersonateUser();

        $this->post('/dominion/town-crier', ['show_bot_attacks' => '1'])
            ->assertRedirect('/dashboard');
    }

    public function testPreferencePersistsOnlyForSelectedDominionAndPreservesFilters(): void
    {
        $dominion = $this->createViewer(['hide_bot_attack_badge' => 'on']);
        $otherDominion = $this->createDominion($this->createUser(), $dominion->round, null, $dominion->realm);
        $otherSettings = $otherDominion->settings;

        $this->post('/dominion/town-crier/0', [
            'show_bot_attacks' => '1',
            'type' => 'invasions',
            'dominion' => $otherDominion->id,
        ])->assertRedirect(route('dominion.town-crier', [
            'realmNumber' => 0,
            'type' => 'invasions',
            'dominion' => $otherDominion->id,
        ]));

        $this->assertTrue($dominion->fresh()->settings['show_bot_attacks']);
        $this->assertSame('on', $dominion->fresh()->settings['hide_bot_attack_badge']);
        $this->assertSame($otherSettings, $otherDominion->fresh()->settings);

        $this->post('/dominion/town-crier', ['show_bot_attacks' => '0'])
            ->assertRedirect(route('dominion.town-crier'));

        $this->assertFalse($dominion->fresh()->settings['show_bot_attacks']);
    }

    #[DataProvider('invalidPreferences')]
    public function testInvalidPreferencesDoNotChangeSettings(array $input, string $field): void
    {
        $dominion = $this->createViewer(['show_bot_attacks' => false]);
        $settings = $dominion->settings;

        $this->from('/dominion/town-crier')->post('/dominion/town-crier', $input)
            ->assertRedirect('/dominion/town-crier')
            ->assertSessionHasErrors($field);

        $this->assertSame($settings, $dominion->fresh()->settings);
    }

    public static function invalidPreferences(): array
    {
        return [
            'missing boolean' => [[], 'show_bot_attacks'],
            'invalid boolean' => [['show_bot_attacks' => 'yes'], 'show_bot_attacks'],
            'array boolean' => [['show_bot_attacks' => ['1']], 'show_bot_attacks'],
            'invalid event type' => [['show_bot_attacks' => '1', 'type' => 'invalid'], 'type'],
            'invalid dominion' => [['show_bot_attacks' => '1', 'dominion' => 0], 'dominion'],
        ];
    }

    public function testTownCrierHidesBotAttacksByDefaultAndKeepsTheirUnreadCursor(): void
    {
        $dominion = $this->createViewer();
        $lastSeen = $dominion->town_crier_last_seen->toDateTimeString();
        $bot = $this->createBot($dominion);
        $this->createInvasion($bot, $dominion);
        $playerAttack = $this->createInvasion($dominion, $bot);

        $this->get('/dominion/town-crier')
            ->assertOk()
            ->assertViewHas('showBotAttacks', false)
            ->assertViewHas('hiddenBotAttackCount', 1)
            ->assertViewHas('gameEvents', fn ($events) => $events->pluck('id')->all() === [(string) $playerAttack->id])
            ->assertSee('1 bot attack hidden by your filter.')
            ->assertDontSee('<span class="badge text-bg-bot">Bot attack</span>', false);

        $dominion->refresh();
        $this->assertTrue($dominion->town_crier_last_seen->equalTo(now()));
        $this->assertSame($lastSeen, $dominion->settings['town_crier_bot_attacks_last_seen']);
    }

    public function testShowingPreviouslyHiddenBotAttackLabelsItAndMarksItRead(): void
    {
        $dominion = $this->createViewer([
            'show_bot_attacks' => true,
            'town_crier_bot_attacks_last_seen' => now()->subHours(2)->toDateTimeString(),
        ]);
        $dominion->update(['town_crier_last_seen' => now()->subMinutes(10)]);
        $attack = $this->createInvasion($this->createBot($dominion), $dominion);

        $this->get('/dominion/town-crier')
            ->assertOk()
            ->assertViewHas('hiddenBotAttackCount', 0)
            ->assertViewHas('gameEvents', fn ($events) => $events->pluck('id')->all() === [(string) $attack->id])
            ->assertSee('<span class="badge text-bg-bot">Bot attack</span>', false);

        $this->assertSame(now()->toDateTimeString(), $dominion->fresh()->settings['town_crier_bot_attacks_last_seen']);
    }

    #[DataProvider('nonInvasionFilters')]
    public function testFilteredNewsPreservesUnreadBotAttacks(string $type, string $eventType, int $newsAgeInMinutes): void
    {
        $dominion = $this->createViewer([
            'show_bot_attacks' => true,
            'town_crier_bot_attacks_last_seen' => now()->subHours(3)->toDateTimeString(),
        ]);
        $dominion->update(['town_crier_last_seen' => now()->subHour()]);
        $lastSeen = $dominion->town_crier_last_seen->toDateTimeString();
        $botLastSeen = $dominion->settings['town_crier_bot_attacks_last_seen'];
        $bot = $this->createBot($dominion);
        $attack = $this->createInvasion($bot, $dominion);
        $attack->created_at = now()->subMinutes(90);
        $attack->save();

        $news = $this->createInvasion($dominion, $bot);
        $news->type = $eventType;
        $news->created_at = now()->subMinutes($newsAgeInMinutes);
        if ($type === 'wars') {
            $news->source()->associate($dominion->realm);
            $news->target()->associate($dominion->realm);
        }
        $news->save();
        $service = app(GameEventService::class);
        $this->assertSame(['events' => $newsAgeInMinutes < 60 ? 1 : 0, 'botAttacks' => 1], $service->getUnreadCounts($dominion));

        $view = app(TownCrierController::class)->getIndex(
            Request::create('/dominion/town-crier', 'GET', ['type' => $type])
        );

        $this->assertSame([(string) $news->id], $view->getData()['gameEvents']->pluck('id')->all());
        $dominion->refresh();
        $this->assertSame($newsAgeInMinutes < 60 ? now()->toDateTimeString() : $lastSeen, $dominion->town_crier_last_seen->toDateTimeString());
        $this->assertSame($botLastSeen, $dominion->settings['town_crier_bot_attacks_last_seen']);
        $this->assertSame(['events' => 0, 'botAttacks' => 1], $service->getUnreadCounts($dominion));
    }

    public static function nonInvasionFilters(): array
    {
        return [
            'old wars' => ['wars', 'war_canceled', 120],
            'old wonders' => ['wonders', 'wonder_attacked', 120],
            'old raids' => ['raids', 'raid_attacked', 120],
            'unread wars' => ['wars', 'war_canceled', 30],
            'unread wonders' => ['wonders', 'wonder_attacked', 30],
            'unread raids' => ['raids', 'raid_attacked', 30],
        ];
    }

    public function testQuickRevealFormAlwaysEnablesBotAttacksAndPreservesFilters(): void
    {
        $dominion = $this->createViewer();
        $dominion->realm->update(['number' => 0]);
        $this->createInvasion($this->createBot($dominion), $dominion);

        $response = $this->get('/dominion/town-crier/0?type=invasions&dominion=' . $dominion->id)
            ->assertOk();
        $document = \Dom\HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
        $form = $document->querySelector('form.d-inline');

        $this->assertNotNull($form);
        $this->assertSame(route('dominion.town-crier.preferences', 0), $form->getAttribute('action'));
        $this->assertSame('post', $form->getAttribute('method'));
        $this->assertSame(1, $form->querySelectorAll('[name="show_bot_attacks"]')->length);
        $this->assertSame('1', $form->querySelector('[name="show_bot_attacks"]')->getAttribute('value'));
        $this->assertSame('invasions', $form->querySelector('[name="type"]')->getAttribute('value'));
        $this->assertSame((string) $dominion->id, $form->querySelector('[name="dominion"]')->getAttribute('value'));
    }

    public function testSingleDominionViewDoesNotMarkTownCrierRead(): void
    {
        $dominion = $this->createViewer(['show_bot_attacks' => true]);
        $lastSeen = $dominion->town_crier_last_seen->toDateTimeString();
        $this->createInvasion($this->createBot($dominion), $dominion);

        $this->get('/dominion/town-crier?dominion=' . $dominion->id)
            ->assertOk()
            ->assertSee('<span class="badge text-bg-bot">Bot attack</span>', false);

        $this->assertSame($lastSeen, $dominion->fresh()->town_crier_last_seen->toDateTimeString());
        $this->assertArrayNotHasKey('town_crier_bot_attacks_last_seen', $dominion->fresh()->settings);
    }

    public function testSidebarCountsAndBadgeSettingAreIndependentOfEventFilter(): void
    {
        $dominion = $this->createViewer(['show_bot_attacks' => true]);
        $bot = $this->createBot($dominion);
        $this->createInvasion($bot, $dominion);
        $this->createInvasion($dominion, $bot);

        $this->get('/dominion/status')->assertOk()
            ->assertSee('aria-label="1 unread events, excluding bot attacks">1</span>', false)
            ->assertSee('aria-label="1 unread bot attacks">1</span>', false);

        $this->post('/dominion/misc/settings', ['hide_bot_attack_badge' => 'on'])
            ->assertRedirect(route('dominion.misc.settings'))
            ->assertSessionHasNoErrors();

        $this->assertTrue($dominion->fresh()->settings['show_bot_attacks']);
        $this->assertSame('on', $dominion->fresh()->settings['hide_bot_attack_badge']);

        $this->get('/dominion/status')->assertOk()
            ->assertSee('aria-label="1 unread events, excluding bot attacks">1</span>', false)
            ->assertDontSee('aria-label="1 unread bot attacks">1</span>', false);

        $this->post('/dominion/misc/settings')->assertSessionHasNoErrors();
        $this->assertNull($dominion->fresh()->settings['hide_bot_attack_badge']);
        $this->assertTrue($dominion->fresh()->settings['show_bot_attacks']);
    }

    protected function createViewer(array $settings = []): Dominion
    {
        $this->withoutVite();
        $this->travelTo(now()->startOfMinute());
        $league = RoundLeague::query()->firstOrCreate(
            ['key' => 'town_crier_http_test'],
            ['description' => 'Town Crier HTTP tests']
        );
        $round = $league->rounds()->create([
            'number' => 1,
            'name' => 'Town Crier HTTP tests',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(46),
            'pack_size' => 6,
        ]);
        $dominion = $this->createDominion(
            $this->createAndImpersonateUser(),
            $round,
            null,
            $this->createRealm($round),
            [
                'settings' => $settings,
                'town_crier_last_seen' => now()->subHours(2),
                'protection_ticks_remaining' => 0,
                'protection_finished' => true,
            ]
        );

        return $this->selectDominion($dominion);
    }

    protected function createBot(Dominion $viewer): Dominion
    {
        return $this->createDominion($this->createUser(), $viewer->round, null, $viewer->realm, [
            'user_id' => null,
        ]);
    }

    protected function createInvasion(Dominion $source, Dominion $target): GameEvent
    {
        $event = new GameEvent([
            'round_id' => $source->round_id,
            'source_type' => Dominion::class,
            'source_id' => $source->id,
            'target_type' => Dominion::class,
            'target_id' => $target->id,
            'type' => 'invasion',
            'data' => ['result' => ['success' => false]],
        ]);
        $event->created_at = now()->subHour();
        $event->save();

        return $event;
    }
}

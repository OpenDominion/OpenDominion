<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Http\Controllers\Dominion\TownCrierController;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\GameEventService;
use OpenDominion\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TownCrierPreferencesTest extends AbstractTestCase
{
    public function testDefaultRequiresAuthentication(): void
    {
        $this->post('/dominion/misc/town-crier-default', ['type' => 'all'])
            ->assertRedirect('/auth/login');
    }

    public function testDefaultRequiresAnAvailableDominion(): void
    {
        $this->createAndImpersonateUser();

        $this->post('/dominion/misc/town-crier-default', ['type' => 'all'])
            ->assertRedirect('/dashboard');
    }

    public function testDefaultPersistsOnlyForSelectedDominionAndPreservesFiltersAndResources(): void
    {
        $dominion = $this->createViewer([
            'hide_bot_attack_badge' => 'on',
            'other_preference' => ['enabled' => true],
        ]);
        $otherDominion = $this->createDominion($this->createUser(), $dominion->round, null, $dominion->realm);
        $otherSettings = $otherDominion->settings;
        app(SelectorService::class)->getUserSelectedDominion();
        $originalAttributes = $dominion->fresh()->getRawOriginal();
        $this->travel(1)->seconds();

        $this->post('/dominion/misc/town-crier-default/0', [
            'type' => 'invasions',
            'dominion' => $otherDominion->id,
        ])->assertRedirect(route('dominion.town-crier', [
            'realmNumber' => 0,
            'type' => 'invasions',
            'dominion' => $otherDominion->id,
        ]))->assertSessionHasNoErrors();

        $dominion->refresh();
        $this->assertSame('invasions', $dominion->settings['town_crier_default_type']);
        $this->assertSame('on', $dominion->settings['hide_bot_attack_badge']);
        $this->assertSame(['enabled' => true], $dominion->settings['other_preference']);
        $this->assertSame($otherSettings, $otherDominion->fresh()->settings);
        foreach ($originalAttributes as $attribute => $value) {
            if (str_starts_with($attribute, 'resource_') || in_array($attribute, ['updated_at', 'last_tick_at', 'town_crier_last_seen'], true)) {
                $this->assertSame($value, $dominion->getRawOriginal($attribute), $attribute);
            }
        }
    }

    public function testLockedDominionCannotChangeDefault(): void
    {
        $dominion = $this->createViewer(['town_crier_default_type' => 'wars']);
        $dominion->update(['locked_at' => now()]);

        $this->from('/dominion/town-crier')->post('/dominion/misc/town-crier-default', ['type' => 'all'])
            ->assertRedirect('/dominion/town-crier')
            ->assertSessionHasErrors();

        $this->assertSame('wars', $dominion->fresh()->settings['town_crier_default_type']);
    }

    public function testEveryEventTypeCanBeSavedAsDefault(): void
    {
        $dominion = $this->createViewer();

        foreach (['all', 'all_without_bots', 'invasions', 'invasions_without_bots', 'wars', 'wonders', 'raids'] as $type) {
            $this->post('/dominion/misc/town-crier-default', ['type' => $type])
                ->assertRedirect(route('dominion.town-crier', ['type' => $type]))
                ->assertSessionHasNoErrors();

            $this->assertSame($type, $dominion->fresh()->settings['town_crier_default_type']);
        }
    }

    public function testDefaultCanBeSavedWithNullSettings(): void
    {
        $dominion = $this->createViewer();
        $dominion->update(['settings' => null]);
        app(SelectorService::class)->getUserSelectedDominion()->refresh();

        $this->post('/dominion/misc/town-crier-default', ['type' => 'all_without_bots'])
            ->assertRedirect(route('dominion.town-crier', ['type' => 'all_without_bots']))
            ->assertSessionHasNoErrors();

        $this->assertSame(['town_crier_default_type' => 'all_without_bots'], $dominion->fresh()->settings);
    }

    #[DataProvider('invalidDefaults')]
    public function testInvalidDefaultsDoNotChangeSettings(array $input, string $field): void
    {
        $dominion = $this->createViewer(['town_crier_default_type' => 'wars']);
        $settings = $dominion->settings;

        $this->from('/dominion/town-crier')->post('/dominion/misc/town-crier-default', $input)
            ->assertRedirect('/dominion/town-crier')
            ->assertSessionHasErrors($field);

        $this->assertSame($settings, $dominion->fresh()->settings);
    }

    public static function invalidDefaults(): array
    {
        return [
            'missing type' => [[], 'type'],
            'invalid type' => [['type' => 'invalid'], 'type'],
            'array type' => [['type' => ['all']], 'type'],
            'zero dominion' => [['type' => 'all', 'dominion' => 0], 'dominion'],
            'negative dominion' => [['type' => 'all', 'dominion' => -1], 'dominion'],
            'fractional dominion' => [['type' => 'all', 'dominion' => 1.5], 'dominion'],
            'array dominion' => [['type' => 'all', 'dominion' => [1]], 'dominion'],
        ];
    }

    #[DataProvider('townCrierVisibilityChoices')]
    public function testReadingVisibleNewsClearsBothCountsWithoutChangingSettingsOrTimestamp(string $query, string $type): void
    {
        $dominion = $this->createViewer();
        $bot = $this->createBot($dominion);
        $botAttack = $this->createInvasion($bot, $dominion);
        $playerAttack = $this->createInvasion($dominion, $bot);
        $service = app(GameEventService::class);
        $this->assertSame(['events' => 1, 'botAttacks' => 1], $service->getUnreadCounts($dominion));
        app(SelectorService::class)->getUserSelectedDominion();
        $updatedAt = $dominion->fresh()->getRawOriginal('updated_at');
        $this->travel(1)->seconds();

        $response = $this->get('/dominion/town-crier' . $query)
            ->assertOk()
            ->assertViewHas('type', $type);
        $this->assertSame($type === 'all', str_contains($response->getContent(), route('dominion.event', [$botAttack->id])));
        $this->assertEqualsCanonicalizing(
            $type === 'all' ? [$botAttack->id, $playerAttack->id] : [$playerAttack->id],
            $response->viewData('gameEvents')->pluck('id')->all()
        );

        $dominion->refresh();
        $this->assertTrue($dominion->town_crier_last_seen->equalTo(now()));
        $this->assertSame($updatedAt, $dominion->getRawOriginal('updated_at'));
        $this->assertSame(['events' => 0, 'botAttacks' => 0], $service->getUnreadCounts($dominion));
        $this->assertSame([], $dominion->settings);
    }

    public static function townCrierVisibilityChoices(): array
    {
        return [
            'default includes bot attacks' => ['', 'all'],
            'explicitly exclude bot attacks' => ['?type=all_without_bots', 'all_without_bots'],
        ];
    }

    #[DataProvider('botExcludingFilters')]
    public function testHiddenBotAttacksStillAdvanceSharedCursor(string $type): void
    {
        $dominion = $this->createViewer();
        $this->createInvasion($this->createBot($dominion), $dominion);
        $this->travel(1)->seconds();

        $this->get('/dominion/town-crier?type=' . $type)->assertOk()
            ->assertViewHas('gameEvents', fn ($events) => $events->isEmpty());

        $dominion->refresh();
        $this->assertSame(now()->toDateTimeString(), $dominion->town_crier_last_seen->toDateTimeString());
        $this->assertSame(['events' => 0, 'botAttacks' => 0], app(GameEventService::class)->getUnreadCounts($dominion));
    }

    public static function botExcludingFilters(): array
    {
        return [
            'all excluding bot attacks' => ['all_without_bots'],
            'invasions excluding bot attacks' => ['invasions_without_bots'],
        ];
    }

    public function testAlreadySeenHiddenBotAttacksDoNotAdvanceSharedCursor(): void
    {
        $dominion = $this->createViewer();
        $this->createInvasion($this->createBot($dominion), $dominion)->update(['created_at' => now()->subHours(2)]);
        $dominion->update(['town_crier_last_seen' => now()->subHour()]);
        $lastSeen = $dominion->town_crier_last_seen->toDateTimeString();
        $this->travel(1)->seconds();

        $this->get('/dominion/town-crier?type=all_without_bots')->assertOk();

        $this->assertSame($lastSeen, $dominion->fresh()->town_crier_last_seen->toDateTimeString());
    }

    public function testSavedDefaultExcludesBotAttacksAndTemporaryOverrideDoesNotPersist(): void
    {
        $dominion = $this->createViewer(['town_crier_default_type' => 'all_without_bots']);
        $attack = $this->createInvasion($this->createBot($dominion), $dominion);

        $this->get('/dominion/town-crier')->assertOk()
            ->assertViewHas('type', 'all_without_bots')
            ->assertViewHas('gameEvents', fn ($events) => $events->isEmpty());

        $this->get('/dominion/town-crier?type=all')->assertOk()
            ->assertViewHas('type', 'all')
            ->assertViewHas('gameEvents', fn ($events) => $events->pluck('id')->all() === [(string) $attack->id])
            ->assertSee(route('dominion.event', [$attack->id]), false);

        $this->assertSame('all_without_bots', $dominion->fresh()->settings['town_crier_default_type']);
        $this->get('/dominion/town-crier')->assertOk()->assertViewHas('type', 'all_without_bots');
    }

    public function testInvalidQueryTypeUsesSavedDefaultAndInvalidSavedDefaultUsesFallback(): void
    {
        $dominion = $this->createViewer(['town_crier_default_type' => 'invasions']);

        foreach (['?type=invalid', '?type[]=all'] as $query) {
            $this->get('/dominion/town-crier' . $query)->assertOk()->assertViewHas('type', 'invasions');
        }
        $this->assertSame('invasions', $dominion->fresh()->settings['town_crier_default_type']);

        app(SelectorService::class)->getUserSelectedDominion()->update(['settings' => ['town_crier_default_type' => 'invalid']]);
        $this->get('/dominion/town-crier')->assertOk()->assertViewHas('type', 'all');
    }

    #[DataProvider('nonInvasionFilters')]
    public function testFilteredNewsOnlyAdvancesSharedCursorWhenVisibleNewsIsUnread(string $type, string $eventType, int $newsAgeInMinutes): void
    {
        $dominion = $this->createViewer();
        $dominion->update(['town_crier_last_seen' => now()->subHour()]);
        $lastSeen = $dominion->town_crier_last_seen->toDateTimeString();
        $bot = $this->createBot($dominion);
        $attack = $this->createInvasion($bot, $dominion);
        $attack->created_at = now()->subMinutes(20);
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
        $this->assertSame(['events' => 0, 'botAttacks' => $newsAgeInMinutes < 60 ? 0 : 1], $service->getUnreadCounts($dominion));
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

    public function testMakeDefaultFormPreservesFiltersAndDisablesForCurrentDefault(): void
    {
        $dominion = $this->createViewer();
        $this->createRealm($dominion->round)->update(['number' => 0]);
        $url = '/dominion/town-crier/0?type=invasions&dominion=' . $dominion->id;
        $response = $this->get($url)->assertOk();
        $document = \Dom\HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
        $form = $document->querySelector('#town-crier-default-form');

        $this->assertNotNull($form);
        $this->assertSame(route('dominion.misc.town-crier-default', 0), $form->getAttribute('action'));
        $this->assertSame('post', strtolower($form->getAttribute('method')));
        $this->assertSame('invasions', $form->querySelector('[name="type"]')->getAttribute('value'));
        $this->assertSame((string) $dominion->id, $form->querySelector('[name="dominion"]')->getAttribute('value'));
        $this->assertSame('Set as default', trim($form->querySelector('button')->textContent));
        $this->assertFalse($form->querySelector('button')->hasAttribute('disabled'));

        $this->post('/dominion/misc/town-crier-default/0', ['type' => 'invasions', 'dominion' => $dominion->id])
            ->assertSessionHasNoErrors();
        app(SelectorService::class)->getUserSelectedDominion()->refresh();
        $response = $this->get($url)->assertOk();
        $document = \Dom\HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
        $button = $document->querySelector('#town-crier-default-form button');
        $this->assertSame('Default', trim($button->textContent));
        $this->assertTrue($button->hasAttribute('disabled'));
    }

    public function testSingleDominionViewDoesNotMarkTownCrierRead(): void
    {
        $dominion = $this->createViewer(['town_crier_default_type' => 'all']);
        $lastSeen = $dominion->town_crier_last_seen->toDateTimeString();
        $attack = $this->createInvasion($this->createBot($dominion), $dominion);

        $this->get('/dominion/town-crier?dominion=' . $dominion->id)
            ->assertOk()
            ->assertSee(route('dominion.event', [$attack->id]), false);

        $this->assertSame($lastSeen, $dominion->fresh()->town_crier_last_seen->toDateTimeString());
    }

    public function testSidebarCountsAndBadgeSettingAreIndependentOfDefaultFilter(): void
    {
        $dominion = $this->createViewer(['town_crier_default_type' => 'all_without_bots']);
        $bot = $this->createBot($dominion);
        $this->createInvasion($bot, $dominion);
        $this->createInvasion($dominion, $bot);

        $this->get('/dominion/status')->assertOk()
            ->assertSee('aria-label="1 unread events, excluding bot attacks">1</span>', false)
            ->assertSee('aria-label="1 unread bot attacks">1</span>', false);

        $this->post('/dominion/misc/settings', ['hide_bot_attack_badge' => 'on'])
            ->assertRedirect(route('dominion.misc.settings'))
            ->assertSessionHasNoErrors();

        $this->assertSame('all_without_bots', $dominion->fresh()->settings['town_crier_default_type']);
        $this->assertSame('on', $dominion->fresh()->settings['hide_bot_attack_badge']);

        $this->get('/dominion/status')->assertOk()
            ->assertSee('aria-label="1 unread events, excluding bot attacks">1</span>', false)
            ->assertDontSee('aria-label="1 unread bot attacks">1</span>', false);

        $this->post('/dominion/misc/settings')->assertSessionHasNoErrors();
        $this->assertNull($dominion->fresh()->settings['hide_bot_attack_badge']);
        $this->assertSame('all_without_bots', $dominion->fresh()->settings['town_crier_default_type']);
    }

    public function testOpCenterEventRowsDoNotShowBotBadgesWithOrWithoutContextFlag(): void
    {
        $dominion = $this->createViewer();
        $attack = $this->createInvasion($this->createBot($dominion), $dominion);
        $viewData = [
            'selectedDominion' => $dominion,
            'gameEvent' => $attack,
            'dominionIds' => [$dominion->id],
            'rangeCalculator' => app(RangeCalculator::class),
        ];

        foreach ([[], ['fromOpCenter' => true]] as $context) {
            $html = view('partials.dominion.game-event', array_merge($viewData, $context))->render();

            $this->assertStringContainsString('fended off an attack', $html);
            $this->assertStringNotContainsString('>Bot</span>', $html);
        }
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

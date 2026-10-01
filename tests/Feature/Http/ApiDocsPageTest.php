<?php

namespace OpenDominion\Tests\Feature\Http;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use OpenDominion\Mappers\GameEventMapper;
use OpenDominion\Tests\AbstractTestCase;

class ApiDocsPageTest extends AbstractTestCase
{
    private const ENDPOINT_LABELS = [
        'Rounds',
        'Search',
        'Realms',
        'Town Crier',
        'My Dominion',
        'My Realm',
        'Op Center',
        'Dominion Overview',
        'Op Archive',
    ];

    private const ERROR_CODES = [
        'missing_api_key',
        'invalid_api_key',
        'dominion_locked',
        'round_not_started',
        'round_ended',
        'not_found',
        'invalid_parameter',
        'same_realm',
        'rate_limited',
        'server_error',
    ];

    public function testPageIsAccessibleToGuests(): void
    {
        $this->get('/api-docs')
            ->assertOk()
            ->assertSee('API Documentation');
    }

    public function testPageIsAccessibleToLoggedInUsers(): void
    {
        $this->createAndImpersonateUser();

        $this->get(route('api-docs'))
            ->assertOk()
            ->assertSee('API Documentation');
    }

    public function testSettingsPageLinksToThePage(): void
    {
        $settingsView = file_get_contents(resource_path('views/pages/dominion/settings.blade.php'));

        $this->assertIsString($settingsView);
        $this->assertStringContainsString("route('api-docs')", $settingsView);
    }

    public function testPageDocumentsEveryV1Endpoint(): void
    {
        $html = $this->get(route('api-docs'))->assertOk()->getContent();

        $endpoints = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route) => Str::contains($route->getActionName(), '\\Api\\V1\\'))
            ->map(fn (RoutingRoute $route) => Str::after($route->uri(), 'v1'))
            ->values();

        $this->assertCount(9, $endpoints);

        foreach (self::ENDPOINT_LABELS as $label) {
            $this->assertStringContainsString('>' . $label . '</h5>', $html);
        }

        foreach ($endpoints as $endpoint) {
            $this->assertStringContainsString('<code>' . $endpoint . '</code>', $html);
        }
    }

    public function testPageDocumentsAuthenticationParametersAndErrors(): void
    {
        $html = $this->get(route('api-docs'))->assertOk()->getContent();

        $this->assertStringContainsString('X-API-Key', $html);
        $this->assertStringContainsString('Authorization: Bearer', $html);
        $this->assertStringContainsString(url('/api/v1'), $html);

        foreach (GameEventMapper::PUBLIC_TYPES as $eventType) {
            $this->assertStringContainsString('<code>' . $eventType . '</code>', $html);
        }
        $this->assertStringNotContainsString('wonder_invasion', $html);

        foreach (['max_age_hours', 'limit', 'since', 'type', 'realm'] as $parameter) {
            $this->assertStringContainsString('<code>' . $parameter . '</code>', $html);
        }

        foreach (self::ERROR_CODES as $errorCode) {
            $this->assertStringContainsString('<code>' . $errorCode . '</code>', $html);
        }
    }
}

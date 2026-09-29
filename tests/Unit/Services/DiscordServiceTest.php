<?php

namespace OpenDominion\Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use OpenDominion\Models\DiscordUser;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\DiscordService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class DiscordServiceTest extends AbstractBrowserKitTestCase
{
    /** @var array<int, array> Requests made by the mocked client */
    protected $transactions = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.discord_client_id' => 'testing', 'app.discord_bot_token' => 'testing']);
    }

    /**
     * Returns a DiscordService with a mocked HTTP client.
     *
     * @param array<int, Response> $responses
     * @return DiscordService
     */
    protected function serviceWithResponses(array $responses): DiscordService
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->transactions));

        return new DiscordService(new Client(['handler' => $stack]));
    }

    /**
     * @return Realm
     */
    protected function realm(): Realm
    {
        $round = new Round(['number' => 1]);
        $round->discord_guild_id = '111';

        $realm = new Realm(['round_id' => 1, 'number' => 4]);
        $realm->discord_category_id = '222';
        $realm->discord_role_id = '333';
        $realm->setRelation('round', $round);

        return $realm;
    }

    /**
     * @return DiscordUser
     */
    protected function discordUser(): DiscordUser
    {
        return new DiscordUser(['discord_user_id' => '444']);
    }

    /**
     * @return array<int, string> Method and path of each request made
     */
    protected function requestLog(): array
    {
        return array_map(function (array $transaction): string {
            return $transaction['request']->getMethod() . ' ' . $transaction['request']->getUri()->getPath();
        }, $this->transactions);
    }

    public function testGrantRealmAccessWritesAMemberOverwriteOnTheCategory(): void
    {
        $service = $this->serviceWithResponses([new Response(204)]);

        $service->grantRealmAccess($this->discordUser(), $this->realm());

        $this->assertEquals(['PUT /api/channels/222/permissions/444'], $this->requestLog());

        $body = json_decode((string)$this->transactions[0]['request']->getBody(), true);
        $this->assertEquals(1, $body['type']);
        $this->assertEquals('0', $body['deny']);
        $this->assertNotEmpty($body['allow']);
    }

    public function testRevokeRealmAccessDeletesTheOverwrite(): void
    {
        $service = $this->serviceWithResponses([new Response(204)]);

        $service->revokeRealmAccess($this->discordUser(), $this->realm());

        $this->assertEquals(['DELETE /api/channels/222/permissions/444'], $this->requestLog());
    }

    public function testRevokeRealmAccessDoesNothingWithoutACategory(): void
    {
        $service = $this->serviceWithResponses([]);
        $realm = $this->realm();
        $realm->discord_category_id = null;

        $this->assertFalse($service->revokeRealmAccess($this->discordUser(), $realm));
        $this->assertEquals([], $this->requestLog());
    }

    public function testJoiningGrantsAnOverwriteAndNeverAssignsARole(): void
    {
        config(['app.discord_use_roles' => false]);

        $service = $this->serviceWithResponses([
            new Response(404, [], '{"message": "Unknown Member"}'), // member lookup
            new Response(201, [], '{}'), // guild join
            new Response(204), // category overwrite
            new Response(200, [], '[]') // channel lookup for the join message
        ]);

        $service->joinDiscordGuild($this->discordUser(), $this->realm(), 'access-token');

        $this->assertEquals([
            'GET /api/guilds/111/members/444',
            'PUT /api/guilds/111/members/444',
            'PUT /api/channels/222/permissions/444',
            'GET /api/guilds/111/channels'
        ], $this->requestLog());

        $joinBody = json_decode((string)$this->transactions[1]['request']->getBody(), true);
        $this->assertEquals([], $joinBody['roles']);
    }

    public function testJoiningAssignsARoleWhenRolesAreEnabled(): void
    {
        config(['app.discord_use_roles' => true]);

        $service = $this->serviceWithResponses([
            new Response(200, [], '{"roles": ["999"]}'), // already a guild member
            new Response(200, [], '{}'), // role assignment
            new Response(200, [], '[]') // channel lookup for the join message
        ]);

        $service->joinDiscordGuild($this->discordUser(), $this->realm(), 'access-token');

        $this->assertEquals([
            'GET /api/guilds/111/members/444',
            'PATCH /api/guilds/111/members/444',
            'GET /api/guilds/111/channels'
        ], $this->requestLog());

        $roleBody = json_decode((string)$this->transactions[1]['request']->getBody(), true);
        $this->assertEquals(['999', '333'], $roleBody['roles']);
    }
}

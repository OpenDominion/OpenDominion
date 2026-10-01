<?php

namespace OpenDominion\Tests\Unit\Views;

use OpenDominion\Helpers\DiscordHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class JoinDiscordPartialTest extends AbstractBrowserKitTestCase
{
    /**
     * Renders the join-discord partial for a dominion in a Discord enabled realm.
     *
     * @return string
     */
    protected function renderPartial(): string
    {
        $round = new Round(['number' => 1]);
        $round->discord_guild_id = '111';

        $realm = new Realm(['round_id' => 1, 'number' => 4]);
        $realm->setRelation('round', $round);

        $dominion = new Dominion();
        $dominion->setRelation('realm', $realm);
        $dominion->setRelation('round', $round);

        return view('partials.dominion.join-discord', [
            'selectedDominion' => $dominion,
            'discordHelper' => app(DiscordHelper::class)
        ])->render();
    }

    public function testJoinButtonOpensTheDisclaimerInsteadOfLinkingStraightToDiscord(): void
    {
        $html = $this->renderPartial();

        $this->assertStringContainsString('data-bs-target="#joinDiscordModal"', $html);
        $this->assertStringContainsString('id="joinDiscordModal"', $html);
        $this->assertStringContainsString('Before you join', $html);
    }

    public function testTheOnlyLinkToDiscordIsInsideTheDisclaimer(): void
    {
        $html = $this->renderPartial();

        $authorizeUrl = e(app(DiscordHelper::class)->getDiscordConnectUrl('join'));

        $this->assertEquals(1, substr_count($html, $authorizeUrl));
        $this->assertStringContainsString('Continue to Discord', $html);
        $this->assertLessThan(
            strpos($html, $authorizeUrl),
            strpos($html, 'id="joinDiscordModal"'),
            'The authorize link must appear inside the modal.'
        );
    }

    public function testDisclaimerPointsAtTheDiscordFixAndTheCouncilAlternative(): void
    {
        $html = $this->renderPartial();

        $this->assertStringContainsString('docs.discord.com/developers/change-log#august-12-2026', $html);
        $this->assertStringContainsString('temporary', $html);
        $this->assertStringContainsString(e(route('dominion.council')), $html);
    }

    public function testDisclaimerIsHiddenWhenTheRoundHasNoGuild(): void
    {
        $round = new Round(['number' => 1]);

        $realm = new Realm(['round_id' => 1, 'number' => 4]);
        $realm->setRelation('round', $round);

        $dominion = new Dominion();
        $dominion->setRelation('realm', $realm);
        $dominion->setRelation('round', $round);

        $html = view('partials.dominion.join-discord', [
            'selectedDominion' => $dominion,
            'discordHelper' => app(DiscordHelper::class)
        ])->render();

        $this->assertStringNotContainsString('joinDiscordModal', $html);
    }
}

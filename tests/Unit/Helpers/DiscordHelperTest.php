<?php

namespace OpenDominion\Tests\Unit\Helpers;

use OpenDominion\Helpers\DiscordHelper;
use OpenDominion\Models\Realm;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use RuntimeException;

class DiscordHelperTest extends AbstractBrowserKitTestCase
{
    /** @var DiscordHelper */
    protected $discordHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discordHelper = $this->app->make(DiscordHelper::class);
    }

    /**
     * @param int $roundId
     * @param int $number
     * @return Realm
     */
    protected function realm(int $roundId, int $number): Realm
    {
        return new Realm(['round_id' => $roundId, 'number' => $number]);
    }

    public function testLabelComesFromThePool(): void
    {
        $label = $this->discordHelper->getRealmLabel($this->realm(1, 1));

        $this->assertContains($label, DiscordHelper::REALM_LABELS);
    }

    public function testLabelIsDeterministic(): void
    {
        $this->assertEquals(
            $this->discordHelper->getRealmLabel($this->realm(1, 7)),
            $this->discordHelper->getRealmLabel($this->realm(1, 7))
        );
    }

    public function testLabelsAreUniqueWithinARound(): void
    {
        $labels = [];
        for ($number = 1; $number <= count(DiscordHelper::REALM_LABELS); $number++) {
            $labels[] = $this->discordHelper->getRealmLabel($this->realm(1, $number));
        }

        $this->assertCount(count($labels), array_unique($labels));
    }

    public function testLabelsDifferBetweenRounds(): void
    {
        $firstRound = [];
        $secondRound = [];
        for ($number = 1; $number <= 15; $number++) {
            $firstRound[] = $this->discordHelper->getRealmLabel($this->realm(1, $number));
            $secondRound[] = $this->discordHelper->getRealmLabel($this->realm(2, $number));
        }

        $this->assertNotEquals($firstRound, $secondRound);
    }

    public function testLabelNeverContainsTheRealmNumber(): void
    {
        for ($number = 1; $number <= count(DiscordHelper::REALM_LABELS); $number++) {
            $label = $this->discordHelper->getRealmLabel($this->realm(1, $number));

            $this->assertDoesNotMatchRegularExpression('/\d/', $label);
        }
    }

    public function testLabelThrowsWhenThePoolIsExhausted(): void
    {
        $this->expectException(RuntimeException::class);

        $this->discordHelper->getRealmLabel($this->realm(1, count(DiscordHelper::REALM_LABELS) + 1));
    }

    public function testPermissionsIncludeMentionEveryone(): void
    {
        $this->assertEquals(
            0x0000020000,
            $this->discordHelper->getPermissionsBitwise() & 0x0000020000
        );
    }

    public function testLabelThrowsForAnInvalidRealmNumber(): void
    {
        $this->expectException(RuntimeException::class);

        $this->discordHelper->getRealmLabel($this->realm(1, 0));
    }
}

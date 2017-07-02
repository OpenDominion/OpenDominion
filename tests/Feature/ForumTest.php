<?php

namespace OpenDominion\Tests\Feature;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\Race;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class ForumTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;
    const FORUM_LIST = 'div.chatter_sidebar';

    /**
     * Test accessing the forums as guest.
     *
     * @return void
     */
    public function testGuestAccessToForum()
    {
        $this->visit('forums');
        $this->seeText('All Topics');
        $this->seeText('New Topic');
    }

    /**
     * Test that anonymous posting is not allowed.
     */
    public function testGuestHasToLogIntoPost()
    {
        $parameters = [
            'title' => 'Test title',
            'chatter_category_id' => 1,
            'body' => '<p>Test body</p>',
            '_token' => csrf_token(),
        ];
        $this->post('forums/topic', $parameters);
        $this->assertRedirectedTo(route('auth.login'));
    }

    /**
     * Test that the general forums are present.
     */
    public function testDefaultForumsArePresentForGuests()
    {
        $this->seed(CoreDataSeeder::class);
        $this->visit('forums');
        $this->seeInForumList('General');
        $this->seeInForumList('Help');
        $this->seeInForumList('Off-topic');
    }

    /**
     * Test your own realm is in the forum list.
     */
    public function testRealmCouncilIsPresent()
    {
        $this->seed(CoreDataSeeder::class);

        $round = $this->createRound();
        $user = $this->createAndImpersonateUser();
        $dominion = $this->createDominion($user, $round);

        $this->visit('forums');
        $realmName = $dominion->realm->name;
        $this->seeInForumList($realmName);
    }

    /**
     * Test that you don't see other realms forums.
     */
    public function testOtherRealmCouncilIsNotPresent()
    {
        $this->seed(CoreDataSeeder::class);

        $round = $this->createRound();

        $goodRace = Race::where('alignment', 'good')->firstOrFail();
        $evilRace = Race::where('alignment', 'evil')->firstOrFail();

        $otherUser = $this->createUser();
        $myUser = $this->createUser();

        $otherDominion = $this->createDominion($otherUser, $round, $goodRace);
        $myDominion = $this->createDominion($myUser, $round, $evilRace);

        $otherRealmName = $otherDominion->realm->name;
        $myRealmName = $myDominion->realm->name;

        $this->be($myUser);
        $this->visit('forums');
        $this->dontSeeInForumList($otherRealmName);
        $this->seeInForumList($myRealmName);
    }

    private function seeInForumList($text)
    {
        return $this->seeInElement(self::FORUM_LIST, $text);
    }

    private function dontSeeInForumList($text)
    {
        return $this->dontSeeInElement(self::FORUM_LIST, $text);
    }
}

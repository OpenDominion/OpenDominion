<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Http\Middleware\PreventRequestForgery;
use OpenDominion\Models\MessageBoard;
use OpenDominion\Models\User;
use OpenDominion\Services\MessageBoardService;
use OpenDominion\Tests\AbstractTestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MessageBoardAnnouncementsTest extends AbstractTestCase
{
    use DatabaseTransactions;

    protected MessageBoard\Category $announcementsCategory;
    protected MessageBoard\Category $generalCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);

        Role::findOrCreate('Administrator', 'web');

        $this->announcementsCategory = MessageBoard\Category::where('slug', 'announcements')->firstOrFail();
        $this->generalCategory = MessageBoard\Category::firstOrCreate(
            ['slug' => 'general-test'],
            ['name' => 'General Test', 'role_required' => null]
        );
    }

    protected function adminUser(): User
    {
        $user = $this->createUser();
        $user->assignRole('Administrator');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return $user->fresh();
    }

    public function testAdministratorCanCreateAnnouncementThreadWithAllHomepageFields()
    {
        $admin = $this->adminUser();
        $this->be($admin);

        $response = $this->post('/message-board/create', [
            'category' => $this->announcementsCategory->id,
            'title' => 'Round 51 Announcement',
            'body' => 'The new round begins soon.',
            'homepage_display' => 1,
            'homepage_preset' => 'round',
            'homepage_subtitle' => 'Pack deadline June 14.',
            'homepage_url' => 'https://example.com/round-51',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();
        $thread = MessageBoard\Thread::where('title', 'Round 51 Announcement')->firstOrFail();

        $this->assertTrue($thread->homepage_display);
        $this->assertSame('round', $thread->homepage_preset);
        $this->assertSame('Pack deadline June 14.', $thread->homepage_subtitle);
        $this->assertSame('https://example.com/round-51', $thread->homepage_url);
    }

    public function testServiceDefaultsPresetToAnnouncementWhenDisplayTrueButPresetMissing()
    {
        $admin = $this->adminUser();
        $service = app(MessageBoardService::class);

        $thread = $service->createThread(
            $admin,
            $this->announcementsCategory,
            'Untyped announcement',
            'body',
            ['homepage_display' => true]
        );

        $this->assertTrue($thread->homepage_display);
        $this->assertSame('announcement', $thread->homepage_preset);
    }

    public function testServiceDropsHomepageFieldsWhenCategoryIsNotAnnouncements()
    {
        $admin = $this->adminUser();
        $service = app(MessageBoardService::class);

        $thread = $service->createThread(
            $admin,
            $this->generalCategory,
            'A general thread',
            'body',
            [
                'homepage_display' => true,
                'homepage_preset' => 'round',
                'homepage_subtitle' => 'should be ignored',
                'homepage_url' => 'https://example.com/x',
            ]
        );

        $fresh = $thread->fresh();
        $this->assertFalse($fresh->homepage_display);
        $this->assertNull($fresh->homepage_preset);
        $this->assertNull($fresh->homepage_subtitle);
        $this->assertNull($fresh->homepage_url);
    }

    public function testForHomepageScopeMatchesAnnouncementsWithDisplayTrue()
    {
        $admin = $this->adminUser();
        $service = app(MessageBoardService::class);

        $shown = $service->createThread($admin, $this->announcementsCategory, 'Shown', 'body', ['homepage_display' => true]);
        $hiddenByToggle = $service->createThread($admin, $this->announcementsCategory, 'Hidden by toggle', 'body', ['homepage_display' => false]);
        $wrongCategory = $service->createThread($admin, $this->generalCategory, 'Wrong category', 'body');
        // Direct DB poke to bypass the service guard and prove the scope filters by category, not just toggle.
        MessageBoard\Thread::where('id', $wrongCategory->id)->update(['homepage_display' => true]);

        $flagged = $service->createThread($admin, $this->announcementsCategory, 'Flagged', 'body', ['homepage_display' => true]);
        MessageBoard\Thread::where('id', $flagged->id)->update(['flagged_for_removal' => true]);

        $homepage = MessageBoard\Thread::forHomepage()->get();

        $this->assertTrue($homepage->contains('id', $shown->id));
        $this->assertFalse($homepage->contains('id', $hiddenByToggle->id));
        $this->assertFalse($homepage->contains('id', $wrongCategory->id));
        $this->assertFalse($homepage->contains('id', $flagged->id));
    }

    public function testEditThreadUpdatesTitleBodyAndHomepageFields()
    {
        $admin = $this->adminUser();
        $service = app(MessageBoardService::class);

        $thread = $service->createThread($admin, $this->announcementsCategory, 'Old title', 'Old body', [
            'homepage_display' => true,
            'homepage_preset' => 'announcement',
        ]);

        $updated = $service->editThread($thread->fresh(), [
            'title' => 'New title',
            'body' => 'New body',
            'homepage_display' => false,
            'homepage_preset' => 'patch',
            'homepage_subtitle' => 'patch teaser',
            'homepage_url' => 'https://example.com/patch',
        ]);

        $this->assertSame('New title', $updated->title);
        $this->assertSame('New body', $updated->body);
        $this->assertFalse($updated->homepage_display);
        $this->assertSame('patch', $updated->homepage_preset);
        $this->assertSame('patch teaser', $updated->homepage_subtitle);
        $this->assertSame('https://example.com/patch', $updated->homepage_url);
    }

    public function testEditThreadLeavesHomepageFieldsUntouchedOnNonAnnouncementsThread()
    {
        $admin = $this->adminUser();
        $service = app(MessageBoardService::class);

        $thread = $service->createThread($admin, $this->generalCategory, 'general', 'body');

        $service->editThread($thread->fresh(), [
            'title' => 'edited',
            'body' => 'edited',
            'homepage_display' => true,
            'homepage_preset' => 'round',
            'homepage_subtitle' => 'should be ignored',
            'homepage_url' => 'https://example.com/x',
        ]);

        $thread->refresh();
        $this->assertSame('edited', $thread->title);
        $this->assertFalse($thread->homepage_display);
        $this->assertNull($thread->homepage_preset);
        $this->assertNull($thread->homepage_subtitle);
        $this->assertNull($thread->homepage_url);
    }

    public function testGetEditThreadIsBlockedForNonAdministrators()
    {
        $admin = $this->adminUser();
        $service = app(MessageBoardService::class);
        $thread = $service->createThread($admin, $this->announcementsCategory, 'Title', 'body');

        $regularUser = $this->createAndImpersonateUser();
        $response = $this->get("/message-board/thread/{$thread->id}/edit");

        $response->assertRedirect(route('message-board.thread', $thread));
    }

    public function testPostEditThreadIsBlockedForNonAdministrators()
    {
        $admin = $this->adminUser();
        $service = app(MessageBoardService::class);
        $thread = $service->createThread($admin, $this->announcementsCategory, 'Original', 'body');

        $this->createAndImpersonateUser();
        $response = $this->post("/message-board/thread/{$thread->id}/edit", [
            'title' => 'Hijacked',
            'body' => 'Hijacked',
        ]);

        $response->assertStatus(403);
        $this->assertSame('Original', $thread->fresh()->title);
    }

    public function testHomepageRendersChronicleWhenAnnouncementsExist()
    {
        $admin = $this->adminUser();
        $service = app(MessageBoardService::class);
        $service->createThread($admin, $this->announcementsCategory, 'Visible Announcement', 'long body text here', [
            'homepage_display' => true,
            'homepage_preset' => 'patch',
            'homepage_subtitle' => 'visible teaser',
        ]);

        $response = $this->get('/', ['HTTP_REFERER' => 'foo']);
        $response->assertStatus(200);
        $response->assertSee('From the Herald');
        $response->assertSee('Visible Announcement');
        $response->assertSee('visible teaser');
        $response->assertSee('chronicle-entry-patch', false);
    }

    public function testHomepageHidesChronicleWhenNoAnnouncementsQualify()
    {
        $response = $this->get('/', ['HTTP_REFERER' => 'foo']);
        $response->assertStatus(200);
        $response->assertDontSee('From the Herald');
    }

    public function testHomepageSubtitleFallsBackToTruncatedBody()
    {
        $admin = $this->adminUser();
        $service = app(MessageBoardService::class);
        $service->createThread($admin, $this->announcementsCategory, 'No Subtitle Thread', 'This is the body text that should appear as the teaser fallback.', [
            'homepage_display' => true,
            'homepage_preset' => 'announcement',
        ]);

        $response = $this->get('/', ['HTTP_REFERER' => 'foo']);
        $response->assertStatus(200);
        $response->assertSee('This is the body text');
    }

    public function testHomepageUrlFallsBackToThreadRoute()
    {
        $admin = $this->adminUser();
        $service = app(MessageBoardService::class);
        $thread = $service->createThread($admin, $this->announcementsCategory, 'No URL Override', 'body', [
            'homepage_display' => true,
        ]);

        $response = $this->get('/', ['HTTP_REFERER' => 'foo']);
        $response->assertStatus(200);
        $response->assertSee(route('message-board.thread', $thread), false);
    }
}

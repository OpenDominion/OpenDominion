<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\MessageBoard;
use OpenDominion\Services\MessageBoardService;
use OpenDominion\Tests\AbstractTestCase;

class MessageBoardContentModerationTest extends AbstractTestCase
{
    use DatabaseTransactions;

    protected MessageBoard\Category $category;
    protected MessageBoardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = MessageBoard\Category::firstOrCreate(
            ['slug' => 'general-test'],
            ['name' => 'General Test', 'role_required' => null]
        );

        $this->service = app(MessageBoardService::class);
    }

    public function testThreadWithScriptTagInBodyIsAutoFlagged(): void
    {
        $user = $this->createUser();

        $thread = $this->service->createThread(
            $user,
            $this->category,
            'Plain title',
            'Hello <script>alert(1)</script>'
        );

        $this->assertTrue($thread->fresh()->flagged_for_removal);
    }

    public function testThreadWithEventHandlerInBodyIsAutoFlagged(): void
    {
        $user = $this->createUser();

        $thread = $this->service->createThread(
            $user,
            $this->category,
            'plain',
            '<img src=x onerror=alert(1)>'
        );

        $this->assertTrue($thread->fresh()->flagged_for_removal);
    }

    public function testThreadWithJavascriptUrlIsAutoFlagged(): void
    {
        $user = $this->createUser();

        $thread = $this->service->createThread(
            $user,
            $this->category,
            'plain',
            '[click](javascript:alert(1))'
        );

        $this->assertTrue($thread->fresh()->flagged_for_removal);
    }

    public function testThreadWithMaliciousTitleIsAutoFlagged(): void
    {
        $user = $this->createUser();

        $thread = $this->service->createThread(
            $user,
            $this->category,
            '<script>alert(1)</script>',
            'totally innocent body'
        );

        $this->assertTrue($thread->fresh()->flagged_for_removal);
    }

    public function testCleanThreadIsNotAutoFlagged(): void
    {
        $user = $this->createUser();

        $thread = $this->service->createThread(
            $user,
            $this->category,
            'Hello world',
            'This is a perfectly normal post about strategy.'
        );

        $this->assertFalse($thread->fresh()->flagged_for_removal);
    }

    public function testReplyWithScriptTagIsAutoFlagged(): void
    {
        $author = $this->createUser();
        $thread = $this->service->createThread($author, $this->category, 'title', 'body');

        $replier = $this->createUser();
        $post = $this->service->postReply($replier, $thread, '<script>alert(1)</script>');

        $this->assertTrue($post->fresh()->flagged_for_removal);
    }

    public function testCleanReplyIsNotAutoFlagged(): void
    {
        $author = $this->createUser();
        $thread = $this->service->createThread($author, $this->category, 'title', 'body');

        $replier = $this->createUser();
        $post = $this->service->postReply($replier, $thread, 'I agree with your point.');

        $this->assertFalse($post->fresh()->flagged_for_removal);
    }

    public function testReplyWithFlaggedWordIsAutoFlagged(): void
    {
        config(['moderation.flagged_words' => ['badword']]);

        $author = $this->createUser();
        $thread = $this->service->createThread($author, $this->category, 'title', 'body');

        $replier = $this->createUser();
        $post = $this->service->postReply($replier, $thread, 'This contains badword in it');

        $this->assertTrue($post->fresh()->flagged_for_removal);
    }

    public function testFlaggedWordRespectsWordBoundary(): void
    {
        config(['moderation.flagged_words' => ['ass']]);

        $author = $this->createUser();
        $thread = $this->service->createThread($author, $this->category, 'title', 'body');

        $replier = $this->createUser();
        $post = $this->service->postReply($replier, $thread, 'I am in the same class as you.');

        $this->assertFalse($post->fresh()->flagged_for_removal);
    }

    public function testFlaggedWordMatchIsCaseInsensitive(): void
    {
        config(['moderation.flagged_words' => ['badword']]);

        $author = $this->createUser();
        $thread = $this->service->createThread($author, $this->category, 'title', 'body');

        $replier = $this->createUser();
        $post = $this->service->postReply($replier, $thread, 'Look at this BadWord here.');

        $this->assertTrue($post->fresh()->flagged_for_removal);
    }
}

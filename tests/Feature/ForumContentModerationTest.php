<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Forum;
use OpenDominion\Models\Round;
use OpenDominion\Services\ForumService;
use OpenDominion\Tests\AbstractTestCase;

class ForumContentModerationTest extends AbstractTestCase
{
    use DatabaseTransactions;

    protected Round $round;
    protected Dominion $dominion;
    protected ForumService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->round = $this->createRound();
        $user = $this->createUser();
        $this->dominion = $this->createDominion($user, $this->round);

        // Exit building phase so ForumService->guardLockedDominion passes.
        $this->dominion->protection_ticks_remaining = 0;
        $this->dominion->save();

        $this->service = app(ForumService::class);
    }

    public function testThreadWithScriptTagIsAutoFlagged(): void
    {
        $thread = $this->service->createThread(
            $this->dominion,
            'Plain title',
            'Hello <script>alert(1)</script>'
        );

        $this->assertTrue($thread->fresh()->flagged_for_removal);
    }

    public function testThreadWithEventHandlerIsAutoFlagged(): void
    {
        $thread = $this->service->createThread(
            $this->dominion,
            'plain',
            '<img src=x onerror=alert(1)>'
        );

        $this->assertTrue($thread->fresh()->flagged_for_removal);
    }

    public function testThreadWithJavascriptUrlIsAutoFlagged(): void
    {
        $thread = $this->service->createThread(
            $this->dominion,
            'plain',
            '[click](javascript:alert(1))'
        );

        $this->assertTrue($thread->fresh()->flagged_for_removal);
    }

    public function testThreadWithMaliciousTitleIsAutoFlagged(): void
    {
        $thread = $this->service->createThread(
            $this->dominion,
            '<script>alert(1)</script>',
            'totally innocent body'
        );

        $this->assertTrue($thread->fresh()->flagged_for_removal);
    }

    public function testCleanThreadIsNotAutoFlagged(): void
    {
        $thread = $this->service->createThread(
            $this->dominion,
            'Strategy chat',
            'Should we hit realm 3 tonight?'
        );

        $this->assertFalse($thread->fresh()->flagged_for_removal);
    }

    public function testReplyWithScriptTagIsAutoFlagged(): void
    {
        $thread = $this->service->createThread($this->dominion, 'title', 'body');
        $post = $this->service->postReply($this->dominion, $thread, '<script>alert(1)</script>');

        $this->assertTrue($post->fresh()->flagged_for_removal);
    }

    public function testCleanReplyIsNotAutoFlagged(): void
    {
        $thread = $this->service->createThread($this->dominion, 'title', 'body');
        $post = $this->service->postReply($this->dominion, $thread, 'agreed, lets do it');

        $this->assertFalse($post->fresh()->flagged_for_removal);
    }

    public function testReplyWithFlaggedWordIsAutoFlagged(): void
    {
        config(['moderation.flagged_words' => ['badword']]);

        $thread = $this->service->createThread($this->dominion, 'title', 'body');
        $post = $this->service->postReply($this->dominion, $thread, 'this contains badword here');

        $this->assertTrue($post->fresh()->flagged_for_removal);
    }
}

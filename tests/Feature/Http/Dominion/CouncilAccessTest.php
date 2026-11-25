<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Council\Post;
use OpenDominion\Models\Council\Thread;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\AbstractTestCase;

class CouncilAccessTest extends AbstractTestCase
{
    use DatabaseTransactions;

    protected Dominion $author;
    protected Thread $thread;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-4 days midnight');
        $round->update(['assignment_complete' => true]);
        $this->author = $this->createAndSelectDominion($user, $round);
        $this->thread = $this->author->councilThreads()->create([
            'realm_id' => $this->author->realm_id,
            'title' => 'Council meeting',
            'body' => 'Meeting agenda',
            'last_activity' => now(),
        ]);
        $this->post = $this->thread->posts()->create([
            'dominion_id' => $this->author->id,
            'body' => 'Meeting minutes',
        ]);
    }

    public function testLockedAuthorCannotViewDeleteConfirmations(): void
    {
        $this->author->update(['locked_at' => now()]);

        $this->assertDeleteConfirmationsDenied();
        $this->get(route('dominion.council'))->assertRedirectToRoute('dominion.status');
    }

    public function testAuthorWhoChangesRealmsCannotViewDeleteConfirmationsFromTheirFormerRealm(): void
    {
        $newRealm = app(RealmFactory::class)->create($this->author->round, 'good');
        $this->author->update(['realm_id' => $newRealm->id]);

        $this->assertDeleteConfirmationsDenied();
    }

    public function testRealmMonarchCanViewDeleteConfirmations(): void
    {
        $user = $this->createAndImpersonateUser();
        $monarch = $this->createAndSelectDominion($user, $this->author->round, realm: $this->author->realm);
        $monarch->realm->update(['monarch_dominion_id' => $monarch->id]);

        $this->assertDeleteConfirmationsVisible();
    }

    public function testOtherRealmMemberCanReadThreadButCannotModerateIt(): void
    {
        $user = $this->createAndImpersonateUser();
        $this->createAndSelectDominion($user, $this->author->round, realm: $this->author->realm);

        $this->get(route('dominion.council.thread', $this->thread))->assertOk();
        $this->assertDeleteConfirmationsDenied();
    }

    public function testAuthorCanReadCouncilAfterTheRoundEnds(): void
    {
        $this->author->round->update(['end_date' => now()->subDay()]);

        $this->get(route('dominion.council.thread', $this->thread))->assertOk()->assertSeeText($this->post->body);
        $this->assertDeleteConfirmationsVisible();
    }

    private function assertDeleteConfirmationsDenied(): void
    {
        $this->get(route('dominion.council.delete.thread', $this->thread))
            ->assertRedirectToRoute('dominion.council')->assertSessionHasErrors();
        $this->get(route('dominion.council.delete.post', $this->post))
            ->assertRedirectToRoute('dominion.council')->assertSessionHasErrors();
    }

    private function assertDeleteConfirmationsVisible(): void
    {
        $this->get(route('dominion.council.delete.thread', $this->thread))
            ->assertOk()->assertSeeText($this->thread->body)->assertSeeText($this->post->body);
        $this->get(route('dominion.council.delete.post', $this->post))
            ->assertOk()->assertSeeText($this->post->body);
    }
}

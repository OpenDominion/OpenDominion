<?php

namespace OpenDominion\Tests\Feature\Http;

use Illuminate\Support\Carbon;
use OpenDominion\Http\Middleware\PreventRequestForgery;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\GameEventService;
use OpenDominion\Tests\AbstractTestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoundStorylinePagesTest extends AbstractTestCase
{
    protected Round $round;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00:00'));
        $this->withoutMiddleware(PreventRequestForgery::class);

        Role::findOrCreate('Administrator', 'web');

        $this->round = $this->createRound('-5 days');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testAdministratorCanPostStorylineNow(): void
    {
        $this->be($this->adminUser());

        $this->post(route('staff.administrator.rounds.storylines.create', $this->round), [
            'text' => "The blood rain falls.\nThe dead rise.",
            'post_at' => '',
        ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('staff.administrator.rounds.show', $this->round));

        $storyline = $this->round->storylineEvents()->sole();
        $this->assertSame(GameEvent::TYPE_STORYLINE, $storyline->type);
        $this->assertSame($this->round->id, $storyline->round_id);
        $this->assertSame(Round::class, $storyline->source_type);
        $this->assertNull($storyline->target_type);
        $this->assertSame('The blood rain falls.<br />The dead rise.', $storyline->data['text']);
        $this->assertTrue($storyline->created_at->equalTo(now()));
    }

    public function testAdministratorCanScheduleStoryline(): void
    {
        $this->be($this->adminUser());

        $this->post(route('staff.administrator.rounds.storylines.create', $this->round), [
            'text' => 'Ullyaoth stirs.',
            'post_at' => '2026-11-25T18:30',
        ])->assertSessionDoesntHaveErrors();

        $storyline = $this->round->storylineEvents()->sole();
        $this->assertTrue($storyline->created_at->equalTo(Carbon::parse('2026-11-25 18:30:00')));
    }

    public function testMissingTextAndInvalidTimeAreRejected(): void
    {
        $this->be($this->adminUser());

        $this->post(route('staff.administrator.rounds.storylines.create', $this->round), [
            'text' => '',
            'post_at' => 'not a date',
        ])->assertSessionHasErrors(['text', 'post_at']);

        $this->assertSame(0, $this->round->storylineEvents()->count());
    }

    public function testNonAdministratorCannotPostStoryline(): void
    {
        $this->be($this->createUser());

        $this->post(route('staff.administrator.rounds.storylines.create', $this->round), [
            'text' => 'The blood rain falls.',
        ])->assertForbidden();

        $this->assertSame(0, $this->round->storylineEvents()->count());
    }

    public function testAdministratorCanDeleteStoryline(): void
    {
        $this->be($this->adminUser());
        $storyline = app(GameEventService::class)->createStoryline($this->round, 'The blood rain falls.');

        $this->post(route('staff.administrator.rounds.storylines.delete', [$this->round, $storyline]))
            ->assertRedirect(route('staff.administrator.rounds.show', $this->round));

        $this->assertNull(GameEvent::find($storyline->id));
    }

    public function testOnlyThisRoundsStorylinesCanBeDeletedThroughIt(): void
    {
        $this->be($this->adminUser());
        $otherRound = $this->createRound('-5 days');
        $otherStoryline = app(GameEventService::class)->createStoryline($otherRound, 'Elsewhere.');
        $warEvent = GameEvent::create([
            'round_id' => $this->round->id,
            'source_type' => Round::class,
            'source_id' => $this->round->id,
            'type' => 'war_declared',
        ]);

        $this->post(route('staff.administrator.rounds.storylines.delete', [$this->round, $otherStoryline]))
            ->assertNotFound();
        $this->post(route('staff.administrator.rounds.storylines.delete', [$this->round, $warEvent]))
            ->assertNotFound();

        $this->assertNotNull(GameEvent::find($otherStoryline->id));
        $this->assertNotNull(GameEvent::find($warEvent->id));
    }

    public function testRoundShowPageListsPostedAndScheduledStorylines(): void
    {
        $this->be($this->adminUser());
        $service = app(GameEventService::class);
        $service->createStoryline($this->round, 'The blood rain falls.');
        $service->createStoryline($this->round, 'Ullyaoth stirs.', now()->addDay());

        $this->get(route('staff.administrator.rounds.show', $this->round))
            ->assertOk()
            ->assertSee('The blood rain falls.')
            ->assertSee('Ullyaoth stirs.')
            ->assertSee('text-bg-warning', false);
    }

    public function testPostTimeDefaultsToCurrentUtcTime(): void
    {
        $this->be($this->adminUser());

        $this->get(route('staff.administrator.rounds.show', $this->round))
            ->assertOk()
            ->assertSee('name="post_at" id="storyline-post-at" class="form-control" value="2026-11-24T12:00"', false);
    }

    protected function adminUser(): User
    {
        $user = $this->createUser();
        $user->assignRole('Administrator');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }
}

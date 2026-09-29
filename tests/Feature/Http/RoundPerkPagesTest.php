<?php

namespace OpenDominion\Tests\Feature\Http;

use OpenDominion\Http\Middleware\PreventRequestForgery;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundPerk;
use OpenDominion\Models\User;
use OpenDominion\Services\RoundPerkService;
use OpenDominion\Tests\AbstractTestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoundPerkPagesTest extends AbstractTestCase
{
    protected Round $round;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);

        Role::findOrCreate('Administrator', 'web');

        $this->round = $this->createRound('-5 days');
    }

    protected function adminUser(): User
    {
        $user = $this->createUser();
        $user->assignRole('Administrator');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    public function testAdministratorCanCreatePerk(): void
    {
        $this->be($this->adminUser());

        $this->post(route('staff.administrator.rounds.perks.create', $this->round), [
            'key' => 'offense',
            'value' => '5',
            'alignment' => 'evil',
            'from_day' => '',
            'until_day' => '10',
            'name' => 'Blood Moon',
            'description' => 'The moon turns red.',
        ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('staff.administrator.rounds.show', $this->round));

        $perk = $this->round->perks()->firstOrFail();
        $this->assertSame('offense', $perk->key);
        $this->assertSame('5', $perk->value);
        $this->assertSame('evil', $perk->alignment);
        $this->assertNull($perk->from_day);
        $this->assertSame(10, $perk->until_day);
    }

    public function testAdministratorCanEditAndDeletePerk(): void
    {
        $this->be($this->adminUser());
        $perk = app(RoundPerkService::class)->create($this->round, ['key' => 'offense', 'value' => '5']);

        $this->post(route('staff.administrator.rounds.perks.edit', [$this->round, $perk]), [
            'key' => 'defense',
            'value' => '3',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('defense', $perk->fresh()->key);

        $this->post(route('staff.administrator.rounds.perks.delete', [$this->round, $perk]))
            ->assertRedirect(route('staff.administrator.rounds.show', $this->round));

        $this->assertNull(RoundPerk::find($perk->id));
    }

    public function testUnsupportedKeyAndInvertedDaysAreRejected(): void
    {
        $this->be($this->adminUser());

        $this->post(route('staff.administrator.rounds.perks.create', $this->round), [
            'key' => 'not_a_perk',
            'value' => '5',
            'from_day' => '10',
            'until_day' => '5',
        ])->assertSessionHasErrors(['key', 'until_day']);

        $this->assertSame(0, $this->round->perks()->count());
    }

    public function testPerkFromAnotherRoundCannotBeEditedThroughThisRound(): void
    {
        $this->be($this->adminUser());
        $otherRound = $this->createRound('-5 days');
        $perk = app(RoundPerkService::class)->create($otherRound, ['key' => 'offense', 'value' => '5']);

        $this->post(route('staff.administrator.rounds.perks.delete', [$this->round, $perk]))
            ->assertNotFound();
    }

    public function testNonAdministratorCannotCreatePerk(): void
    {
        $this->be($this->createUser());

        $this->post(route('staff.administrator.rounds.perks.create', $this->round), [
            'key' => 'offense',
            'value' => '5',
        ])->assertForbidden();

        $this->assertSame(0, $this->round->perks()->count());
    }

    public function testRoundShowPageListsPerks(): void
    {
        $this->be($this->adminUser());
        app(RoundPerkService::class)->create($this->round, ['key' => 'offense', 'value' => '5', 'name' => 'Blood Moon']);

        $this->get(route('staff.administrator.rounds.show', $this->round))
            ->assertOk()
            ->assertSee('Blood Moon')
            ->assertSee('Offensive power');
    }

    public function testStatusPageShowsRoundPerks(): void
    {
        $user = $this->createAndImpersonateUser();
        $dominion = $this->createDominion($user, $this->round, Race::where('key', 'human')->firstOrFail(), null, [
            'protection_finished' => true,
        ]);

        app(RoundPerkService::class)->create($this->round, [
            'key' => 'offense',
            'value' => '5',
            'name' => 'Blood Moon',
            'description' => 'The moon turns red.',
        ]);
        app(RoundPerkService::class)->create($this->round, [
            'key' => 'food_production',
            'value' => '-10',
            'name' => 'Blight',
            'alignment' => 'evil',
        ]);
        app(RoundPerkService::class)->create($this->round, ['key' => 'defense', 'value' => '3', 'until_day' => 3]);
        app(RoundPerkService::class)->create($this->round, ['key' => 'defense', 'value' => '3', 'from_day' => 30]);
        app(RoundPerkService::class)->create($this->round, ['key' => 'defense', 'value' => '3', 'until_day' => 15]);
        app(RoundPerkService::class)->create($this->round, ['key' => 'defense', 'value' => '3', 'from_day' => 20, 'until_day' => 25]);

        $this->selectDominion($dominion->fresh());

        $this->get(route('dominion.status'))
            ->assertOk()
            ->assertSee('Blood Moon')
            ->assertSee('The moon turns red.')
            ->assertSee('Round Perks')
            ->assertSee('+5%')
            ->assertSee('Blight')
            ->assertSee('<td class="text-muted">-10%</td>', false)
            ->assertSee('Evil races')
            ->assertSee('All races, Expired Day 3')
            ->assertSee('Active Day 30')
            ->assertSee('Active Day 20-25')
            ->assertSee('All races, Ending Day 15');
    }

    public function testStatusPageHidesRoundPerksWhenNoneExist(): void
    {
        $user = $this->createAndImpersonateUser();
        $dominion = $this->createDominion($user, $this->round, Race::where('key', 'human')->firstOrFail(), null, [
            'protection_finished' => true,
        ]);

        $this->selectDominion($dominion->fresh());

        $this->get(route('dominion.status'))
            ->assertOk()
            ->assertDontSee('Round Perks');
    }
}

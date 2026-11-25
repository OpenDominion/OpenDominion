<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\InfoOp;
use OpenDominion\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

class MilitaryCalculatorIntelTest extends AbstractTestCase
{
    use DatabaseTransactions;

    public static function savedReportAccess(): array
    {
        return [
            'explicit lock' => ['-4 days', true, true, false],
            'protected after round start' => ['-4 days', false, false, false],
            'out of protection' => ['-4 days', false, true, true],
            'protected before round start' => ['+1 day', false, false, true],
        ];
    }

    #[DataProvider('savedReportAccess')]
    public function testCalculatorUsesSavedReportsOnlyWhenOpCenterIsAccessible(
        string $roundStart,
        bool $locked,
        bool $protectionFinished,
        bool $mayReadReports
    ): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound($roundStart);
        $viewer = $this->createAndSelectDominion($user, $round);
        $viewer->update([
            'locked_at' => $locked ? now() : null,
            'protection_finished' => $protectionFinished,
        ]);
        $otherRealm = app(RealmFactory::class)->create($round, 'good');
        $target = $this->createDominion($this->createUser(), $round, realm: $otherRealm);
        $report = InfoOp::create([
            'source_realm_id' => $viewer->realm_id,
            'source_dominion_id' => $viewer->id,
            'target_realm_id' => $target->realm_id,
            'target_dominion_id' => $target->id,
            'type' => 'castle_spy',
            'latest' => true,
            'data' => ['walls' => ['rating' => 0.073], 'forges' => ['rating' => 0.041]],
        ]);

        $response = $this->get(route('dominion.calculations.military', ['dominion' => $target->id]))
            ->assertOk()
            ->assertViewIs('pages.dominion.calculations.military');

        $page = new Crawler($response->getContent());
        $this->assertSame($mayReadReports ? '7.3' : '', $page->filter('input[name="calc[walls_percent]"]')->attr('value'));

        $reports = $response->viewData('targetInfoOps');
        if ($mayReadReports) {
            $this->assertSame($report->id, $reports->get('castle_spy')->id);
            return;
        }

        $this->assertTrue($reports->isEmpty());
        $message = $locked
            ? 'Locked dominions are not allowed access to the op center.'
            : 'Dominions in protection are not allowed access to the op center.';
        foreach ([
            route('dominion.op-center'),
            route('dominion.op-center.show', $target),
            route('dominion.op-center.archive', [$target, 'castle_spy']),
        ] as $url) {
            $this->get($url)->assertRedirect()->assertSessionHasErrors();
            $this->assertSame($message, session('errors')->first());
        }
    }

    public static function advisorAccess(): array
    {
        return [
            'own advisors while locked' => [true],
            'explicitly shared advisors while protected' => [false],
        ];
    }

    #[DataProvider('advisorAccess')]
    public function testCalculatorUsesLiveDataWhenAdvisorsAreAvailable(bool $ownAdvisors): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-4 days');
        $viewer = $this->createAndSelectDominion($user, $round);
        $viewer->update(['protection_finished' => false, 'locked_at' => $ownAdvisors ? now() : null]);
        $target = $ownAdvisors ? $viewer : $this->createDominion(
            $this->createUser(),
            $round,
            realm: $viewer->realm,
            attributes: ['settings' => ['realmadvisors' => [$viewer->id => true]]]
        );
        $target->update(['military_draftees' => 371]);

        $response = $this->get(route('dominion.calculations.military', ['dominion' => $target->id]))
            ->assertOk();
        $page = new Crawler($response->getContent());
        $this->assertSame('371', $page->filter('input[name="calc[draftees]"]')->attr('value'));
    }
}

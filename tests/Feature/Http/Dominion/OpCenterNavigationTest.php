<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use OpenDominion\Tests\AbstractTestCase;

class OpCenterNavigationTest extends AbstractTestCase
{
    private const INFORMATION_OPERATIONS = [
        'clear_sight' => ['anchor' => 'op-clear-sight', 'label' => 'Clear Sight'],
        'revelation' => ['anchor' => 'op-revelation', 'label' => 'Revelation'],
        'castle_spy' => ['anchor' => 'op-castle-spy', 'label' => 'Castle Spy'],
        'barracks_spy' => ['anchor' => 'op-barracks-spy', 'label' => 'Barracks Spy'],
        'survey_dominion' => ['anchor' => 'op-survey-dominion', 'label' => 'Survey Dominion'],
        'land_spy' => ['anchor' => 'op-land-spy', 'label' => 'Land Spy'],
        'vision' => ['anchor' => 'op-vision', 'label' => 'Vision'],
        'disclosure' => ['anchor' => 'op-disclosure', 'label' => 'Disclosure'],
    ];

    public function testNavigationLinksToEveryInformationOperationSection(): void
    {
        $html = view('partials.dominion.op-center.navigation')->render();
        $opCenterView = file_get_contents(resource_path('views/pages/dominion/op-center/show.blade.php'));

        $this->assertIsString($opCenterView);
        $this->assertStringContainsString("@include('partials.dominion.op-center.navigation')", $opCenterView);

        foreach (self::INFORMATION_OPERATIONS as $operation) {
            $this->assertStringContainsString('href="#' . $operation['anchor'] . '"', $html);
            $this->assertStringContainsString('>' . $operation['label'] . '</a>', $html);
            $this->assertStringContainsString('id="' . $operation['anchor'] . '"', $opCenterView);
        }

        $this->assertSame(
            count(self::INFORMATION_OPERATIONS),
            substr_count($html, 'btn-outline-primary')
        );
    }

    public function testNavigationLinksBetweenInformationOperationArchives(): void
    {
        $dominionId = 42;
        $archiveView = file_get_contents(resource_path('views/pages/dominion/op-center/archive.blade.php'));
        $html = view('partials.dominion.op-center.navigation', [
            'archiveDominion' => $dominionId,
            'activeInfoOpType' => 'clear_sight',
        ])->render();

        $this->assertIsString($archiveView);
        $this->assertStringContainsString("@include('partials.dominion.op-center.navigation'", $archiveView);

        foreach (self::INFORMATION_OPERATIONS as $type => $operation) {
            $this->assertStringContainsString(
                'href="' . route('dominion.op-center.archive', [$dominionId, $type]) . '"',
                $html
            );
            $this->assertStringContainsString('>' . $operation['label'] . '</a>', $html);
        }

        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
        $this->assertSame(
            count(self::INFORMATION_OPERATIONS),
            substr_count($html, 'btn-outline-primary')
        );
    }
}

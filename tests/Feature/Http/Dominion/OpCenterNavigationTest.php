<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use OpenDominion\Tests\AbstractTestCase;

class OpCenterNavigationTest extends AbstractTestCase
{
    public function testNavigationLinksToEveryInformationOperationSection(): void
    {
        $html = view('partials.dominion.op-center.navigation')->render();
        $opCenterView = file_get_contents(resource_path('views/pages/dominion/op-center/show.blade.php'));

        $this->assertIsString($opCenterView);
        $this->assertStringContainsString("@include('partials.dominion.op-center.navigation')", $opCenterView);

        $expectedOperations = [
            'op-clear-sight' => 'Clear Sight',
            'op-revelation' => 'Revelation',
            'op-castle-spy' => 'Castle Spy',
            'op-barracks-spy' => 'Barracks Spy',
            'op-survey-dominion' => 'Survey Dominion',
            'op-land-spy' => 'Land Spy',
            'op-vision' => 'Vision',
            'op-disclosure' => 'Disclosure',
        ];

        foreach ($expectedOperations as $anchor => $label) {
            $this->assertStringContainsString('href="#' . $anchor . '"', $html);
            $this->assertStringContainsString('>' . $label . '</a>', $html);
            $this->assertStringContainsString('id="' . $anchor . '"', $opCenterView);
        }

        $this->assertSame(count($expectedOperations), substr_count($html, 'btn-outline-primary'));
    }
}

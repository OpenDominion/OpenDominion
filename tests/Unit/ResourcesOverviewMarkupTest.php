<?php

namespace OpenDominion\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ResourcesOverviewMarkupTest extends TestCase
{
    public function testDominionSettingsButtonIsSeparatedFromTheDominionName(): void
    {
        $markup = file_get_contents(__DIR__ . '/../../app/resources/views/partials/resources-overview.blade.php');

        $titlePosition = strpos($markup, '<span class="card-title flex-grow-1">');
        $settingsButtonPosition = strpos($markup, '<a class="btn btn-sm btn-outline-secondary flex-shrink-0 dominion-settings-button"');

        $this->assertNotFalse($titlePosition);
        $this->assertNotFalse($settingsButtonPosition);
        $this->assertGreaterThan($titlePosition, $settingsButtonPosition);
        $this->assertStringContainsString('card-header d-flex align-items-center justify-content-between gap-3', $markup);
        $this->assertStringContainsString('aria-label="Dominion settings"', $markup);
        $this->assertStringContainsString('fa fa-sliders', $markup);
        $this->assertStringNotContainsString('fa fa-cog', $markup);
    }
}

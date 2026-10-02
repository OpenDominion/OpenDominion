<?php

namespace OpenDominion\Tests\Unit\Helpers;

use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Models\HeroUpgrade;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class HeroHelperTest extends AbstractBrowserKitTestCase
{
    /** @var HeroHelper */
    protected $heroHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->heroHelper = $this->app->make(HeroHelper::class);
    }

    /**
     * app.js only initializes Bootstrap 5 tooltips; without them the <br> in
     * the title renders literally.
     */
    public function testUpgradeIconUsesBootstrapFiveTooltip(): void
    {
        $upgrade = new HeroUpgrade([
            'name' => 'Lead from the Front',
            'level' => 1,
            'type' => 'doctrine',
            'icon' => 'ra-vertical-banner',
        ]);

        $icon = $this->heroHelper->getUpgradeIcon($upgrade);

        $this->assertStringContainsString('data-bs-toggle="tooltip"', $icon);
        $this->assertStringContainsString('title="Level 1: Lead from the Front<br>(Doctrine)"', $icon);
    }

    public function testLockIconUsesBootstrapFiveTooltip(): void
    {
        $icon = $this->heroHelper->getLockIcon(8);

        $this->assertStringContainsString('data-bs-toggle="tooltip"', $icon);
        $this->assertStringContainsString('title="Level 8: Locked"', $icon);
    }
}

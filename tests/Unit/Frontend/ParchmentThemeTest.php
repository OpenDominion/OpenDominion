<?php

namespace OpenDominion\Tests\Unit\Frontend;

use PHPUnit\Framework\TestCase;

class ParchmentThemeTest extends TestCase
{
    public function testThemeUsesForestGreenPrimaryAccentPalette(): void
    {
        $theme = file_get_contents(dirname(__DIR__, 3) . '/app/resources/sass/_theme-parchment.scss');

        $this->assertIsString($theme);
        $this->assertStringContainsString('--od-primary:          #2d5944;', $theme);
        $this->assertStringContainsString('--od-primary-dark:     #1f3f31;', $theme);
        $this->assertStringContainsString('--od-primary-bg-soft:  #dfe8d9;', $theme);
        $this->assertStringContainsString('--od-accent-soft:      #8eac98;', $theme);
        $this->assertStringContainsString('--od-warning:          #6b513b;', $theme);
        $this->assertStringContainsString('--od-warning-dark:     #4b3829;', $theme);
        $this->assertStringContainsString('--od-warning-on-dark:    #d2bfa2;', $theme);
        $this->assertStringContainsString('--od-warning-on-tooltip: var(--od-warning-on-dark);', $theme);
        $this->assertStringContainsString('--od-link-color:       var(--od-primary);', $theme);
        $this->assertStringContainsString('--od-link-hover-color: var(--od-primary-dark);', $theme);
        $this->assertStringContainsString('--bs-primary-rgb:                45, 89, 68;', $theme);
        $this->assertStringContainsString('--bs-success-rgb:                74, 122, 58;', $theme);
        $this->assertStringContainsString('--bs-info-rgb:                   58, 86, 128;', $theme);
        $this->assertStringContainsString('--bs-warning-rgb:                107, 81, 59;', $theme);
        $this->assertStringContainsString('--bs-danger-rgb:                 139, 42, 30;', $theme);
        $this->assertStringContainsString('--bs-link-color-rgb:            45, 89, 68;', $theme);
        $this->assertStringContainsString('--bs-link-hover-color-rgb:      31, 63, 49;', $theme);
        $this->assertStringContainsString('--bs-focus-ring-color:          rgba(45, 89, 68, .25);', $theme);
        $this->assertStringContainsString('.text-orange                { color: var(--od-warning) !important; }', $theme);
        $this->assertStringContainsString('.app-sidebar .text-orange   { color: var(--od-warning-on-dark) !important; }', $theme);

        foreach (['#8a6010', '#6a4a0c', '#a07018', '#c4902a', '#8a5520', '#6a4018', '#d4a060', '#d4a030', '#b8541a', '#f0d890', 'rgba(138, 96, 16'] as $orangeValue) {
            $this->assertStringNotContainsString($orangeValue, $theme);
        }
    }
}

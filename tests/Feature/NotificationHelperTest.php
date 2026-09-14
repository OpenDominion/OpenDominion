<?php

namespace OpenDominion\Tests\Feature;

use Mockery;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Tests\AbstractTestCase;

class NotificationHelperTest extends AbstractTestCase
{
    /** @var NotificationHelper */
    protected $notificationHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationHelper = app(NotificationHelper::class);
    }

    /**
     * The header dropdown calls getNotificationCategories() twice per unread
     * notification, and each build evaluates twelve route() lookups. Asserting
     * the category builders run exactly once across repeated calls is what
     * keeps that cost from scaling with the notification count again.
     */
    public function testNotificationCategoriesAreBuiltOnlyOnce(): void
    {
        /** @var NotificationHelper|Mockery\MockInterface $notificationHelper */
        $notificationHelper = Mockery::mock(NotificationHelper::class)->makePartial();

        $notificationHelper->shouldReceive('getGeneralTypes')->once()->andReturn([]);
        $notificationHelper->shouldReceive('getHourlyDominionTypes')->once()->andReturn([]);
        $notificationHelper->shouldReceive('getIrregularDominionTypes')->once()->andReturn([]);
        $notificationHelper->shouldReceive('getIrregularRealmTypes')->once()->andReturn([]);

        $notificationHelper->getNotificationCategories();
        $notificationHelper->getNotificationCategories();

        $this->assertTrue(true, 'Mockery verifies each category builder ran exactly once.');
    }

    public function testNotificationCategoriesReturnsEveryCategory(): void
    {
        $categories = $this->notificationHelper->getNotificationCategories();

        $this->assertSame(
            ['general', 'hourly_dominion', 'irregular_dominion', 'irregular_realm'],
            array_keys($categories)
        );
    }

    public function testRepeatedCallsReturnIdenticalCategories(): void
    {
        $this->assertSame(
            $this->notificationHelper->getNotificationCategories(),
            $this->notificationHelper->getNotificationCategories()
        );
    }

    /**
     * Guards the memoized payload itself: the cached array still has to carry
     * the resolved route URLs the dropdown links to, not unevaluated closures
     * or nulls.
     */
    public function testCachedCategoriesRetainResolvedRoutes(): void
    {
        $categories = $this->notificationHelper->getNotificationCategories();

        $this->assertSame(
            route('dominion.explore'),
            $categories['hourly_dominion']['exploration_completed']['route']
        );
        $this->assertSame(
            route('dominion.military'),
            $categories['hourly_dominion']['training_completed']['route']
        );
    }

    /**
     * getDefaultUserNotificationSettings() reads through the same cache, so a
     * broken memoization would surface here as missing defaults.
     */
    public function testDefaultUserNotificationSettingsStillResolve(): void
    {
        $settings = $this->notificationHelper->getDefaultUserNotificationSettings();

        $this->assertSame(
            ['general', 'hourly_dominion', 'irregular_dominion', 'irregular_realm'],
            array_keys($settings)
        );
        $this->assertSame(
            ['email' => false, 'ingame' => true],
            $settings['hourly_dominion']['exploration_completed']
        );
    }
}

<?php

namespace OpenDominion\Tests\Unit\Factories;

use Carbon\Carbon;
use OpenDominion\Factories\RoundFactory;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RoundFactoryTest extends AbstractBrowserKitTestCase
{
    public function testOffensiveActionsEndDateIsSkewedTowardsRoundEnd(): void
    {
        $roundFactory = $this->app->make(RoundFactory::class);
        $endDate = new Carbon('2026-10-01 00:00:00');

        $hoursBeforeRoundEndCounts = array_fill(9, 6, 0);
        for ($i = 0; $i < 2000; $i++) {
            $offensiveActionsEndDate = $roundFactory->getOffensiveActionsEndDate($endDate);

            $this->assertEquals(new Carbon('2026-10-01 00:00:00'), $endDate);

            $secondsBeforeRoundEnd = (int) $offensiveActionsEndDate->diffInSeconds($endDate);
            $this->assertGreaterThan(9 * 3600, $secondsBeforeRoundEnd);
            $this->assertLessThan(15 * 3600, $secondsBeforeRoundEnd);

            $hoursBeforeRoundEndCounts[intdiv($secondsBeforeRoundEnd, 3600)]++;
        }

        $this->assertGreaterThan($hoursBeforeRoundEndCounts[14], $hoursBeforeRoundEndCounts[9]);
        $this->assertGreaterThan(
            $hoursBeforeRoundEndCounts[13] + $hoursBeforeRoundEndCounts[14],
            $hoursBeforeRoundEndCounts[9] + $hoursBeforeRoundEndCounts[10]
        );
    }
}

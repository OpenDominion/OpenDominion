<?php

namespace OpenDominion\Tests\Unit\Models;

use OpenDominion\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUnratedUserResolvesToDefaultRating(): void
    {
        $user = new User();
        $user->rating = 0;

        $this->assertSame(User::DEFAULT_RATING, $user->getEffectiveRating());
    }

    public function testRatedUserKeepsStoredRating(): void
    {
        $user = new User();
        $user->rating = 1725;

        $this->assertSame(1725.0, $user->getEffectiveRating());
    }
}

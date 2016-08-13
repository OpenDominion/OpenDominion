<?php

namespace OpenDominion\Tests\Feature\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\BaseTestCase;

class AuthTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function testGuestCantAccessProtectedPages()
    {
        $this->visit('/auth/logout')
            ->seePageIs('/auth/login');

        $this->visit('/dashboard')
            ->seePageIs('/auth/login');
    }

    public function testAuthenticatedUserCantAccessLoginAndRegisterPages()
    {
        $this->createAndImpersonateUser();

        $this->visit('/auth/login')
            ->seePageIs('/');

        $this->visit('/auth/register')
            ->seePageIs('/');
    }
}

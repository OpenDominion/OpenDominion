<?php

namespace OpenDominion\Tests\Feature\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class AuthTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    public function testGuestCantAccessProtectedPages()
    {
        $this->visit('/dashboard')
            ->seePageIs('/auth/login');

        // todo: expand?
    }

    public function testAuthenticatedUserCantAccessLoginAndRegisterPages()
    {
        $this->createAndImpersonateUser();

        $this->visit('/auth/login')
            ->seePageIs('/dashboard');

        $this->visit('/auth/register')
            ->seePageIs('/dashboard');
    }
}

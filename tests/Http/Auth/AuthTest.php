<?php

namespace OpenDominion\Tests\Http\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class AuthTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    public function testGuestCantAccessProtectedPages()
    {
        $this->visitRoute('dashboard')
            ->seeRouteIs('auth.login');

        // todo: expand?
    }

    public function testAuthenticatedUserCantAccessLoginAndRegisterPages()
    {
        $this->createAndImpersonateUser();

        $this->visitRoute('auth.login')
            ->seeRouteIs('home');

        $this->visitRoute('auth.register')
            ->seeRouteIs('home');
    }
}

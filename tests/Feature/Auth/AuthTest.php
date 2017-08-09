<?php

namespace OpenDominion\Tests\Feature\Auth;

use OpenDominion\Tests\AbstractBrowserKitDatabaseTestCase;

class AuthTest extends AbstractBrowserKitDatabaseTestCase
{
    public function testGuestCantAccessProtectedPages()
    {
        $this->visit('/dashboard')
            ->seePageIs('/auth/login');

        // todo: expand?
    }

    public function testAuthenticatedUserCantAccessLoginAndRegisterPages()
    {
        $this->be($this->user);

        $this->visit('/auth/login')
            ->seePageIs('/');

        $this->visit('/auth/register')
            ->seePageIs('/');
    }
}

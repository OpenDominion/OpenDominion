<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\User;
use OpenDominion\Tests\BaseTestCase;

class AuthTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function testUserCanLogin()
    {
        $password = str_random();
        $user = $this->createUser($password);

        $this->visit('/auth/login')
            ->see('Login')
            ->type($user->email, 'email')
            ->type($password, 'password')
            ->press('Login')
            ->seePageIs('/status')
            ->see('temp status page');
    }

    public function testUserCantLoginWithInvalidCredentials()
    {
        $this->visit('/auth/login')
            ->see('Login')
            ->type('nonexistant@example.com', 'email')
            ->type('somepassword', 'password')
            ->press('Login')
            ->seePageIs('/auth/login')
            ->see('These credentials do not match our records');
    }

    public function testUserCanLogout()
    {
        $this->createAndImpersonateUser();

        $this->visit('/auth/logout')
            ->seePageIs('/');
    }

    public function testUserCanRegister()
    {
        $this->markTestIncomplete();
    }

    public function testGuestCantAccessProtectedPages()
    {
        $this->visit('/auth/logout')
            ->seePageIs('/auth/login');

        $this->visit('/status')
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

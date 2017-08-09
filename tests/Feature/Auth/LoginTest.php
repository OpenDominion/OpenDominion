<?php

namespace OpenDominion\Tests\Feature\Auth;

use OpenDominion\Tests\AbstractBrowserKitDatabaseTestCase;

class LoginTest extends AbstractBrowserKitDatabaseTestCase
{
    public function testUserCanLogin()
    {
        $user = $this->createUser('secret');

        $this->visit('/auth/login')
            ->see('Login')
            ->type($user->email, 'email')
            ->type('secret', 'password')
            ->press('Login')
            ->seePageIs('/dashboard');
        // todo: see logged in user == $user
    }

    public function testUserCanLogout()
    {
        $this->be($this->user);

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->press('Logout')
            ->seePageIs('/')
            ->see('You have been logged out.');
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

    public function testUserCantLoginWhenNotActivated()
    {
        $user = $this->createUser('secret', ['activated' => false]);

        $this->visit('/auth/login')
            ->see('Login')
            ->type($user->email, 'email')
            ->type('secret', 'password')
            ->press('Login')
            ->seePageIs('/auth/login')
            ->see('Your account has not been activated yet. Check your spam folder for the activation email.');
    }
}

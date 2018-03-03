<?php

namespace OpenDominion\Tests\Http\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class LoginTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    public function testLoginPage()
    {
        $this->visitRoute('auth.login')
            ->seeStatusCode(200);
    }

    public function testUserCanLogin()
    {
        $user = $this->createUser('secret');

        $this->visitRoute('auth.login')
            ->see('Login')
            ->type($user->email, 'email')
            ->type('secret', 'password')
            ->press('Login')
            ->seeRouteIs('dashboard')
            ->seeIsAuthenticatedAs($user);
    }

    public function testUserCanLogout()
    {
        $this->createAndImpersonateUser();

        $this->visitRoute('dashboard')
            ->see('Dashboard')
            ->press('Logout')
            ->seeRouteIs('home')
            ->see('You have been logged out.');
    }

    public function testUserCantLoginWithInvalidCredentials()
    {
        $this->visitRoute('auth.login')
            ->see('Login')
            ->type('nonexistant@example.com', 'email')
            ->type('somepassword', 'password')
            ->press('Login')
            ->seeRouteIs('auth.login')
            ->see('These credentials do not match our records');
    }

    public function testUserCantLoginWhenNotActivated()
    {
        $user = $this->createUser('secret', ['activated' => false]);

        $this->visitRoute('auth.login')
            ->see('Login')
            ->type($user->email, 'email')
            ->type('secret', 'password')
            ->press('Login')
            ->seeRouteIs('auth.login')
            ->see('Your account has not been activated yet. Check your spam folder for the activation email.');
    }

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

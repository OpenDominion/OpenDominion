<?php

namespace OpenDominion\Tests\Feature\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\BaseTestCase;

class LoginTest extends BaseTestCase
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
            ->seePageIs('/dashboard')
            ->see("Welcome back, <b>{$user->display_name}</b>.");
    }

    public function testUserCanLogout()
    {
        $this->createAndImpersonateUser();

        $this->visit('/auth/logout')
            ->seePageIs('/');
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
        $password = str_random();
        $user = $this->createUser($password, ['activated' => false]);

        $this->visit('/auth/login')
            ->see('Login')
            ->type($user->email, 'email')
            ->type($password, 'password')
            ->press('Login')
            ->seePageIs('/auth/login')
            ->see('Your account has not been activated yet. Check your spam folder for the activation email.');
    }
}

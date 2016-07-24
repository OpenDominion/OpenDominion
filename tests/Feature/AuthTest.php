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
            ->seePageIs('/dashboard')
            ->see("Welcome back, <b>{$user->display_name}</b>.");
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

    public function testUserCanLogout()
    {
        $this->createAndImpersonateUser();

        $this->visit('/auth/logout')
            ->seePageIs('/');
    }

    public function testUserCanRegister()
    {
        $this->visit('/auth/register')
            ->see('Register')
            ->type('johndoe@example.com', 'email')
            ->type('password', 'password')
            ->type('password', 'password_confirmation')
            ->type('John Doe', 'display_name')
            ->press('Register')
            ->seePageIs('/')
            ->see('You have been successfully registered. An activation email has been dispatched to your address.')
            ->seeInDatabase('users', [
                'email' => 'johndoe@example.com',
                'display_name' => 'John Doe',
                'activated' => false,
                'last_online' => null,
            ]);
    }

    // todo: test register with blank data, duplicate email/display_name, non-matching passwords

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

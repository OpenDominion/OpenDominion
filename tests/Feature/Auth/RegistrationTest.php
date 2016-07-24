<?php

namespace OpenDominion\Tests\Feature\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\BaseTestCase;

class RegistrationTest extends BaseTestCase
{
    use DatabaseMigrations;

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

    public function testUserCantRegisterWithBlankData()
    {
        $this->visit('/auth/register')
            ->see('Register')
            ->press('Register')
            ->seePageIs('/auth/register')
            ->see('The display name field is required.')
            ->see('The email field is required.')
            ->see('The password field is required.');
    }

    public function testUserCantRegisterWithDuplicateEmail()
    {
        $this->createUser(null, ['email' => 'johndoe@example.com']);

        $this->visit('/auth/register')
            ->see('Register')
            ->type('johndoe@example.com', 'email')
            ->type('password', 'password')
            ->type('password', 'password_confirmation')
            ->type('John Doe', 'display_name')
            ->press('Register')
            ->seePageIs('/auth/register')
            ->see('The email has already been taken.');
    }

    public function testUserCantRegisterWithDuplicateDisplayName()
    {
        $this->createUser(null, ['display_name' => 'John Doe']);

        $this->visit('/auth/register')
            ->see('Register')
            ->type('johndoe@example.com', 'email')
            ->type('password', 'password')
            ->type('password', 'password_confirmation')
            ->type('John Doe', 'display_name')
            ->press('Register')
            ->seePageIs('/auth/register')
            ->see('The display name has already been taken.');
    }

    public function testUserCantRegisterWithNonMatchingPasswords()
    {
        $this->visit('/auth/register')
            ->see('Register')
            ->type('johndoe@example.com', 'email')
            ->type('password1', 'password')
            ->type('password2', 'password_confirmation')
            ->type('John Doe', 'display_name')
            ->press('Register')
            ->seePageIs('/auth/register')
            ->see('The password confirmation does not match.');
    }
}

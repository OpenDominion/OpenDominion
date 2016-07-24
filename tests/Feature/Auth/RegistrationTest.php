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
        $this->markTestIncomplete();
    }

    public function testUserCantRegisterWithDuplicateEmail()
    {
        $this->markTestIncomplete();
    }

    public function testUserCantRegisterWithDuplicateDisplayName()
    {
        $this->markTestIncomplete();
    }

    public function testUserCantRegisterWithNonMatchingPasswords()
    {
        $this->markTestIncomplete();
    }
}

<?php

namespace OpenDominion\Tests\Http\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Notification;
use OpenDominion\Models\User;
use OpenDominion\Notifications\User\RegisteredNotification;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RegisterTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    public function testRegistrationPage()
    {
        $this->visitRoute('auth.register')
            ->seeStatusCode(200);
    }

    public function testUserCanRegister()
    {
        $this->visitRoute('auth.register')
            ->see('Register')
            ->type('John Doe', 'display_name')
            ->type('johndoe@example.com', 'email')
            ->type('secret', 'password')
            ->type('secret', 'password_confirmation')
            ->check('terms')
            ->press('Register')
            ->seeRouteIs('home')
            ->see('You have been successfully registered. An activation email has been dispatched to your address.')
            ->seeInDatabase('users', [
                'email' => 'johndoe@example.com',
                'display_name' => 'John Doe',
                'activated' => false,
                'last_online' => null,
            ]);

        $user = User::where('email', 'johndoe@example.com')->firstOrFail();

        Notification::assertSentTo($user, RegisteredNotification::class);
    }

    public function testNewlyRegisteredUserCanActivateAccount()
    {
        $activation_code = str_random();
        $user = $this->createUser(null, [
            'activated' => false,
            'activation_code' => $activation_code,
        ]);

        $this->visitRoute('auth.activate', $activation_code)
            ->seeRouteIs('dashboard')
            ->see('Your account has been activated and you are now logged in.')
            ->seeInDatabase('users', [
                'id' => $user->id,
                'activated' => true,
            ])
            ->seeIsAuthenticated();
    }

    public function testUserCantActivateWithInvalidActivationCode()
    {
        $user = $this->createUser(null, [
            'activated' => false,
            'activation_code' => 'foo',
        ]);

        $this->visitRoute('auth.activate', 'bar')
            ->seeRouteIs('home')
            ->see('Invalid activation code')
            ->dontSeeInDatabase('users', [
                'id' => $user->id,
                'activated' => true,
            ]);
    }

    public function testUserCantRegisterWithBlankData()
    {
        $this->visitRoute('auth.register')
            ->see('Register')
            ->press('Register')
            ->seeRouteIs('auth.register')
            ->see('The display name field is required.')
            ->see('The email field is required.')
            ->see('The password field is required.');
    }

    public function testUserCantRegisterWithDuplicateEmail()
    {
        $this->createUser(null, ['email' => 'johndoe@example.com']);

        $this->visitRoute('auth.register')
            ->see('Register')
            ->type('John Doe', 'display_name')
            ->type('johndoe@example.com', 'email')
            ->type('password', 'password')
            ->type('password', 'password_confirmation')
            ->check('terms')
            ->press('Register')
            ->seeRouteIs('auth.register')
            ->see('The email has already been taken.');
    }

    public function testUserCantRegisterWithDuplicateDisplayName()
    {
        $this->createUser(null, ['display_name' => 'John Doe']);

        $this->visitRoute('auth.register')
            ->see('Register')
            ->type('John Doe', 'display_name')
            ->type('johndoe@example.com', 'email')
            ->type('password', 'password')
            ->type('password', 'password_confirmation')
            ->check('terms')
            ->press('Register')
            ->seeRouteIs('auth.register')
            ->see('The display name has already been taken.');
    }

    public function testUserCantRegisterWithNonMatchingPasswords()
    {
        $this->visitRoute('auth.register')
            ->see('Register')
            ->type('John Doe', 'display_name')
            ->type('johndoe@example.com', 'email')
            ->type('password1', 'password')
            ->type('password2', 'password_confirmation')
            ->check('terms')
            ->press('Register')
            ->seeRouteIs('auth.register')
            ->see('The password confirmation does not match.');
    }

    public function testUserCantRegisterWithoutAgreeingToTheTerms()
    {
        $this->visitRoute('auth.register')
            ->see('Register')
            ->type('John Doe', 'display_name')
            ->type('johndoe@example.com', 'email')
            ->type('password', 'password')
            ->type('password', 'password_confirmation')
            ->press('Register')
            ->seeRouteIs('auth.register')
            ->see('The terms field is required.');
    }
}

<?php

namespace OpenDominion\Tests\Http\Auth;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Notification;
use OpenDominion\Notifications\User\ResetPasswordNotification;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use Password;

class ResetPasswordTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    public function testResetPasswordPage()
    {
        $this->visitRoute('auth.password.request')
            ->seeStatusCode(200);
    }

    public function testUserCanRequestPasswordReset()
    {
        $user = $this->createUser();

        $this->visitRoute('auth.password.request')
            ->see('Reset Password')
            ->type($user->email, 'email')
            ->press('Send Password Reset Link')
            ->see('If that email address exists in our system, we will send it a reset password email.')
            ->seeInDatabase('password_resets', [
                'email' => $user->email,
            ]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function testShowSameMessageIfEmailIsInvalid()
    {
        $this->visitRoute('auth.password.request')
            ->see('Reset Password')
            ->type('nonexistant@example.com', 'email')
            ->press('Send Password Reset Link')
            ->see('If that email address exists in our system, we will send it a reset password email.');

        Notification::assertNothingSent();
    }

    public function testUserCanResetPassword()
    {
        $user = $this->createUser();
        $token = Password::getRepository()->create($user);

        $this->visitroute('auth.password.reset', [$token])
            ->see('Reset Password')
            ->type($user->email, 'email')
            ->type('secret', 'password')
            ->type('secret', 'password_confirmation')
            ->press('Reset Password')
            ->seeRouteIs('home')
            ->see('Your password has been reset and you are now logged in.')
            ->seeIsAuthenticatedAs($user);
    }

    public function testUserCannotResetPasswordWithInvalidToken()
    {
        $user = $this->createUser();
        $token = Password::getRepository()->create($user);

        $this->visitroute('auth.password.reset', ['invalidtoken'])
            ->see('Reset Password')
            ->type($user->email, 'email')
            ->type('secret', 'password')
            ->type('secret', 'password_confirmation')
            ->press('Reset Password')
            ->seeRouteIs('auth.password.reset', ['invalidtoken'])
            ->see('This password reset token for that e-mail address is invalid.');
    }

    public function testUserCannotResetSomeoneElsesPassword()
    {
        $user1 = $this->createUser(null, ['email' => 'email1@example.com']);
        $user2 = $this->createUser(null, ['email' => 'email2@example.com']);
        $token = Password::getRepository()->create($user1);

        $this->visitroute('auth.password.reset', [$token])
            ->see('Reset Password')
            ->type('email2@example.com', 'email')
            ->type('secret', 'password')
            ->type('secret', 'password_confirmation')
            ->press('Reset Password')
            ->seeRouteIs('auth.password.reset', [$token])
            ->see('This password reset token for that e-mail address is invalid.');
    }
}

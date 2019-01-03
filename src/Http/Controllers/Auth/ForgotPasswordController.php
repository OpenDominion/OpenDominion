<?php

namespace OpenDominion\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use OpenDominion\Http\Controllers\AbstractController;

class ForgotPasswordController extends AbstractController
{
    use RedirectsUsers, SendsPasswordResetEmails;

    protected $redirectTo = '/auth/password/reset';

    /**
     * {@inheritdoc}
     */
    public function showLinkRequestForm()
    {
        return view('pages.auth.passwords.email');
    }

    /**
     * {@inheritdoc}
     */
    protected function sendResetLinkResponse(Request $request, $response)
    {
        return redirect()
            ->back()
            ->with(
                'alert-success',
                'If that email address exists in our system, we will send it a reset password email.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        return $this->sendResetLinkResponse($request, $response);
    }
}

<?php

namespace OpenDominion\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use OpenDominion\Http\Controllers\AbstractController;

class ForgotPasswordController extends AbstractController
{
    use SendsPasswordResetEmails;

    /**
     * {@inheritdoc}
     */
    public function showLinkRequestForm()
    {
        return view('pages.auth.passwords.email');
    }
}

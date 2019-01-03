<?php

namespace OpenDominion\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use OpenDominion\Http\Controllers\AbstractController;

class ResetPasswordController extends AbstractController
{
    use ResetsPasswords;

    protected $redirectTo = '/';

    /**
     * {@inheritdoc}
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('pages.auth.passwords.reset')->with([
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function sendResetResponse(Request $request, $response)
    {
        return redirect()
            ->route('home')
            ->with(
                'alert-success',
                'Your password has been reset and you are now logged in.'
            );
    }
}

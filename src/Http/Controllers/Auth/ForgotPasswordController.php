<?php
namespace OpenDominion\Http\Controllers\Auth;
use OpenDominion\Http\Controllers\AbstractController;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
class ForgotPasswordController extends AbstractController
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */
    use SendsPasswordResetEmails;
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    /*
    *   Overrride one of the traits
    *
    */

    public function showLinkRequestForm()
    {
        return view('pages.auth.email');
    }


    public function __construct()
    {
        $this->middleware('guest');
    }

    public function getReset()
    {
        return view('pages.auth.reset');
    }
}


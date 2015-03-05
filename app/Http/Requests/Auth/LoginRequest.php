<?php namespace OpenDominion\Http\Requests\Auth;

use OpenDominion\Http\Requests\Request;

class LoginRequest extends Request
{
    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    public function authorize()
    {
        // Can't login if we're already logged in
        if (app()['auth']->check()) {
            return false;
        }

        return true;
    }
}

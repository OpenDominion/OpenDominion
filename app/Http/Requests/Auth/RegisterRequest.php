<?php namespace OpenDominion\Http\Requests\Auth;

use OpenDominion\Http\Requests\Request;

class RegisterRequest extends Request
{
    public function rules()
    {
        return [
            'email' => 'required|email|unique',
            'password' => 'required|confirmed|min:3',
        ];
    }

    public function authorize()
    {
        // Can't register if we're logged in
        if (app()['auth']->check()) {
            return false;
        }

        return true;
    }
}

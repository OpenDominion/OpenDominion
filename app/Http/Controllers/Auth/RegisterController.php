<?php

namespace OpenDominion\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\User;
use Validator;

class RegisterController extends AbstractController
{
    use RedirectsUsers;

    protected $redirectTo = '/';

    public function getRegister()
    {
        return view('pages.auth.register');
    }

    public function postRegister(Request $request)
    {
        $this->validator($request->all())->validate();

        $this->create($request->all());

        // todo: send activation mail

        $request->session()->flash('alert-success', 'You have been successfully registered. An activation email has been dispatched to your address.');

        return redirect($this->redirectPath());
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'display_name' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);
    }

    protected function create(array $data)
    {
        return User::create([
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'display_name' => $data['display_name'],
            'activation_code' => str_random(),
        ]);
    }
}

<?php namespace OpenDominion\Handlers\Commands\User;

use Illuminate\Contracts\Auth\Guard;
use OpenDominion\Commands\User\LoginCommand;

class LoginCommandHandler
{
    /**
     * @var Guard
     */
    protected $auth;

    /**
     * Create the command handler.
     *
     * @param  Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle the command.
     *
     * @param  LoginCommand $command
     * @return void
     */
    public function handle(LoginCommand $command)
    {
        $this->auth->attempt([
            'email' => $command->email,
            'password' => $command->password
        ], $command->remember);
    }
}

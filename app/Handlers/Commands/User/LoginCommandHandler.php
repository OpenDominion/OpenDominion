<?php namespace OpenDominion\Handlers\Commands\User;

use Illuminate\Contracts\Auth\Guard;
use OpenDominion\Commands\User\LoginCommand;
use OpenDominion\Exceptions\InvalidLoginException;

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
     * @throws InvalidLoginException
     */
    public function handle(LoginCommand $command)
    {
        if (!$this->auth->attempt([
            'email' => $command->email,
            'password' => $command->password
        ], $command->remember)) {
            throw new InvalidLoginException('Invalid email/password combination');
        }
    }
}

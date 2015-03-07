<?php namespace OpenDominion\Handlers\Commands\User;

use OpenDominion\Commands\User\RegisterCommand;
use OpenDominion\Repositories\UserRepository;

class RegisterCommandHandler
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * Create the command handler.
     *
     * @param UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * Handle the command.
     *
     * @param  RegisterCommand $command
     */
    public function handle(RegisterCommand $command)
    {
        // todo: check for active round

        $user = $this->users->create([
            'email' => $command->email,
            'password' => bcrypt($command->password),
            'remember_token' => str_random(),
        ]);

        // todo: create dominion
    }
}

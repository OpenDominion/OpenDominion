<?php namespace OpenDominion\Handlers\Commands\User;

use OpenDominion\Commands\User\RegisterCommand;
use OpenDominion\Exceptions\RegistrationException;
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
     * @throws RegistrationException
     */
    public function handle(RegisterCommand $command)
    {
        // todo: check for active round (in registerrequest?)
        // todo: create user and dominion

        throw new RegistrationException('needs implementation');
    }
}

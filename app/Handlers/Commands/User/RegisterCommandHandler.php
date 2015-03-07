<?php namespace OpenDominion\Handlers\Commands\User;

use OpenDominion\Commands\User\RegisterCommand;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Repositories\UserRepository;

class RegisterCommandHandler
{
    /**
     * @var \OpenDominion\Repositories\DominionRepository
     */
    protected $dominions;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * Create the command handler.
     *
     * @param DominionRepository $dominions
     * @param UserRepository     $users
     */
    public function __construct(DominionRepository $dominions, UserRepository $users)
    {
        $this->dominions = $dominions;
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

        $dominion = $this->dominions->create([
            'user_id' => $user->id,
            'name' => $command->dominion_name,
            'ruler_name' => $command->dominion_ruler_name,
        ]);
    }
}

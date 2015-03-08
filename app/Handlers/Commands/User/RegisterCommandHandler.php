<?php namespace OpenDominion\Handlers\Commands\User;

use OpenDominion\Commands\User\RegisterCommand;
use OpenDominion\Creators\DominionCreator;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Repositories\UserRepository;

class RegisterCommandHandler
{
    /**
     * @var DominionCreator
     */
    protected $dominionCreator;

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
     * @param DominionCreator    $dominionCreator
     * @param DominionRepository $dominions
     * @param UserRepository     $users
     */
    public function __construct(DominionCreator $dominionCreator, DominionRepository $dominions, UserRepository $users)
    {
        $this->dominionCreator = $dominionCreator;
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
        // todo: profanity filter on display_name? ->registerrequest?
        $user = $this->users->create([
            'email' => $command->email,
            'password' => bcrypt($command->password),
//            'activation_token' => str_random(8),
            'display_name' => $command->display_name,
        ]);

        $this->dominionCreator->create($user, $command->dominion_name);
    }
}

<?php namespace OpenDominion\Commands\User;

use OpenDominion\Commands\Command;

class RegisterCommand extends Command
{
    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $dominion_name;


    /**
     * Create a new command instance.
     *
     * @param  string $email
     * @param  string $password
     * @param  string $dominion_name
     */
    public function __construct(
        $email,
        $password,
        $dominion_name
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->dominion_name = $dominion_name;
    }
}

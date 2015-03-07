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
     * @var string
     */
    public $dominion_ruler_name;

    /**
     * Create a new command instance.
     *
     * @param  string $email
     * @param  string $password
     * @param  string $dominion_name
     * @param  string $dominion_ruler_name
     */
    public function __construct(
        $email,
        $password,
        $dominion_name,
        $dominion_ruler_name
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->dominion_name = $dominion_name;
        $this->dominion_ruler_name = $dominion_ruler_name;
    }
}

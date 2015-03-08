<?php namespace OpenDominion\Commands\User;

use OpenDominion\Commands\Command;

class RegisterCommand extends Command
{
    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $display_name;

    /**
     * @var string
     */
    public $dominion_name;

    /**
     * Create a new command instance.
     *
     * @param  string $email
     * @param  string $password
     * @param  string $display_name
     * @param  string $dominion_name
     */
    public function __construct(
        $email,
        $password,
        $display_name,
        $dominion_name
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->display_name = $display_name;
        $this->dominion_name = $dominion_name;
    }
}

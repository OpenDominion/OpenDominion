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
     * Create a new command instance.
     *
     * @param  string $email
     * @param  string $password
     */
    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }
}

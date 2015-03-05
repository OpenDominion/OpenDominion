<?php namespace OpenDominion\Commands\User;

use OpenDominion\Commands\Command;

class LoginCommand extends Command
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
     * @var bool
     */
    public $remember;

    /**
     * Create a new command instance.
     *
     * @param  string $email
     * @param  string $password
     * @param  bool   $remember
     */
    public function __construct($email, $password, $remember)
    {
        $this->email = $email;
        $this->password = $password;
        $this->remember = $remember;
    }
}

<?php

namespace OpenDominion\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use OpenDominion\Models\User;

class UserRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var User */
    protected $user;

    /**
     * Create a new message instance.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.auth.registration', [
            'activation_code' => $this->user->activation_code,
        ]);
    }
}

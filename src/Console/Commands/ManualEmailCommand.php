<?php

namespace OpenDominion\Console\Commands;

use Illuminate\Console\Command;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\User;
use OpenDominion\Notifications\ManualEmailNotification;

class ManualEmailCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command */
    protected $signature = 'app:manual:email
                              {--user= : Send email to user account}
                              {--dominion= : Send email to owner of dominion}
                              {--subject= : Subject line}
                              {--message= : Message body}';

    /** @var string The console command description */
    protected $description = 'Sends a manual email to specified user';

    /**
     * {@inheritDoc}
     */
    public function handle(): void
    {
        $user = null;
        $user_id = $this->option('user');
        $dominion_id = $this->option('dominion');
        $subject = $this->option('subject');
        $message = $this->option('message');

        if (!$user_id && !$dominion_id) {
            $this->info('Please provide a user or dominion id for the message recipient');
            return;
        }

        if (!$subject || !$message) {
            $this->info('Please provide both a subject line and message body');
            return;
        }

        if ($dominion_id) {
            $dominion = Dominion::find($dominion_id);
            if ($dominion !== null) {
                $user_id = $dominion->user_id;
            }
        }

        if ($user_id) {
            $user = User::find($user_id);
        }

        if ($user !== null) {
            $shouldSendGenericNotification = array_get($user->settings['notifications'], 'general.generic.email', false);

            if (!$shouldSendGenericNotification) {
                $this->info("Skipping user {$user->display_name} because of notification settings");
            } else {
                $this->info("Mailing user {$user->display_name}");

                $user->notify(new ManualEmailNotification(
                    $subject,
                    "Hello {$user->display_name}!",
                    [$message],
                    []
                ));
            }
        }
    }
}

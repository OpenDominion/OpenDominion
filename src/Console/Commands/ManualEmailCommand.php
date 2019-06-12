<?php

namespace OpenDominion\Console\Commands;

use Illuminate\Console\Command;
use OpenDominion\Models\User;
use OpenDominion\Notifications\ManualEmailNotification;

class ManualEmailCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command */
    protected $signature = 'app:manual:email {--confirm : Confirm sending emails}';

    /** @var string The console command description */
    protected $description = 'Sends a manual email to certain users';

    /**
     * {@inheritDoc}
     */
    public function handle(): void
    {
        if (!$this->option('confirm')) {
            $this->info('Please run this command with --confirm');
            return;
        }

        if (!$this->confirm('Are you sure you want to run this command to manually send emails to users?')) {
            return;
        }

        $users = User::query()
            ->whereHas('dominions', function ($query) {
                return $query->where('round_id', 12); // Round 12
            })
            ->whereDoesntHave('dominions', function ($query) {
                return $query->where('round_id', 16); // Round 13
            })
            ->orderBy('id')
            ->get()
        ;

        if (!$this->confirm("You will be emailing {$users->count()} users, ARE YOU REALLY FRIGGIN SURE???")) {
            return;
        }

        $utmTags = [
            'utm_source' => 'opendominion',
            'utm_medium' => 'email',
            'utm_campaign' => 'Round 13 Announcement',
        ];

        $url = (route('dashboard') . '/?' . http_build_query($utmTags));

        $usersNotified = 0;

        foreach ($users as $user) {
            $shouldSendGenericNotification = array_get($user->settings['notifications'], 'general.generic.email', false);

            if (!$shouldSendGenericNotification) {
                continue;
            }

            $user->notify(new ManualEmailNotification(
                'OpenDominion Round 13 is about to start!',
                "Hello {$user->display_name}!",
                [
                    "How are you doing, {$user->display_name}? We hope you enjoyed playing OpenDominion in round 12!",
                    'Round 13 is currently open for registration, and starts in less than a day (on Thursday, 13th of June, 00:00 UTC).',
                    'We\'d like to invite you to come play another round! Several new things have been added for this round, including a whopping six new races to choose from!',
                ],
                ['Play OpenDominion' => $url]
            ));

            $usersNotified++;
        }

        $this->info("{$usersNotified} users will be emailed");
    }
}

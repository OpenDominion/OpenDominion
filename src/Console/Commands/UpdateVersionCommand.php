<?php

namespace OpenDominion\Console\Commands;

use Cache;
use Illuminate\Console\Command;
use Log;

class UpdateVersionCommand extends Command
{
    /** @var string The name and signature of the console command */
    protected $signature = 'version:update';

    /** @var string The console command description */
    protected $description = 'Updates game version';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Updating version');

        $env = getenv('APP_ENV');

        $commits = shell_exec('git rev-list --count HEAD');

        $shortHash = trim(shell_exec('git log --pretty="%h" -n1 HEAD'));
        $longHash = trim(shell_exec('git log --pretty="%H" -n1 HEAD'));
        $date = trim(shell_exec('git log --pretty="%ci" -n1 HEAD'));

        $branch = trim(shell_exec('git branch | grep \'* \''));
        $branch = str_replace('* ', '', trim($branch));

        $url = "https://github.com/WaveHack/OpenDominion/commit/{$longHash}";

        Cache::forever('version', "r{$commits} @ {$env} ({$branch} #{$shortHash})");
        Cache::forever('version-date', $date);
        Cache::forever('version-html', "r<strong>{$commits}</strong> @ {$env} ({$branch} <a href=\"{$url}\" target=\"_blank\"><strong>#{$shortHash}</strong></a>)");

        Log::info("Version updated to {$shortHash}");
    }
}

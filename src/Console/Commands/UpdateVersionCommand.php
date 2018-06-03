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

        $version = null;
        $versionHtml = null;

        $tag = trim(shell_exec('git describe --tags --abbrev=0'));
        $date = trim(shell_exec('git log --pretty="%ci" -n1 HEAD'));
        $shortHash = trim(shell_exec('git log --pretty="%h" -n1 HEAD'));
        $longHash = trim(shell_exec('git log --pretty="%H" -n1 HEAD'));

        if ($tag !== '') {
            $url = "https://github.com/WaveHack/OpenDominion/releases/tag/{$tag}";

            $version = $tag;
            $versionHtml = "<strong>{$tag}</strong> (<a href=\"{$url}\" target=\"_blank\">#{$shortHash}</a>)";

        } else {
            $env = getenv('APP_ENV');
            $commits = shell_exec('git rev-list --count HEAD');

            $branch = trim(shell_exec('git branch | grep \'* \''));
            $branch = str_replace('* ', '', trim($branch));

            $url = "https://github.com/WaveHack/OpenDominion/commit/{$longHash}";

            $version = "r{$commits} @ {$env} ({$branch} #{$shortHash})";
            $versionHtml = "r<strong>{$commits}</strong> @ {$env} ({$branch} <a href=\"{$url}\" target=\"_blank\"><strong>#{$shortHash}</strong></a>)";
        }

        Cache::forever('version', $version);
        Cache::forever('version-date', $date);
        Cache::forever('version-html', $versionHtml);

        Log::info("Version updated to {$version}");
    }
}

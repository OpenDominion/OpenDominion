<?php

namespace OpenDominion\Console\Commands;

use Cache;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

class VersionUpdateCommand extends Command implements CommandInterface
{
    protected const REPO_URL = 'https://github.com/WaveHack/OpenDominion';

    /** @var string The name and signature of the console command */
    protected $signature = 'version:update';

    /** @var string The console command description */
    protected $description = 'Updates game version';

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $this->info('Updating version', OutputInterface::VERBOSITY_DEBUG);

        $version = null;
        $versionHtml = null;

        $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
        $tag = trim(shell_exec('git describe --tags'));
        $date = trim(shell_exec('git log --pretty="%ci" -n1 HEAD'));
        $shortHash = trim(shell_exec('git log --pretty="%h" -n1 HEAD'));
        $longHash = trim(shell_exec('git log --pretty="%H" -n1 HEAD'));

        if (($branch === 'master') && ($tag !== '')) {
            if (str_contains($tag, '-')) {
                $tagParts = explode('-', $tag);
                [$tag, $commits] = $tagParts;
            }

            $version = $tag;
            $versionHtml = sprintf(
                '<a href="%1$s/releases/tag/%2$s" target="_blank"><strong>%2$s</strong></a>',
                static::REPO_URL,
                $tag
            );

            /** @noinspection UnSafeIsSetOverArrayInspection */
            if (isset($commits)) {
                $version .= "-{$commits}-g{$shortHash}";
                $versionHtml .= sprintf(
                    '-<a href="%s/compare/%s...%s" target="_blank">%s</a>',
                    static::REPO_URL,
                    $tag,
                    $shortHash,
                    $commits
                );
            }

        } else {
            $env = getenv('APP_ENV');
            $commits = trim(shell_exec('git rev-list --count HEAD'));

            $branch = trim(shell_exec('git branch | grep \'* \''));
            $branch = str_replace('* ', '', trim($branch));

            $url = sprintf(
                '%s/commit/%s',
                static::REPO_URL,
                $longHash
            );

            $version = "r{$commits} @ {$env} ({$branch} #{$shortHash})";
            $versionHtml = "r<strong>{$commits}</strong> @ {$env} ({$branch} <a href=\"{$url}\" target=\"_blank\"><strong>#{$shortHash}</strong></a>)";
        }

        Cache::forever('version', $version);
        Cache::forever('version-date', $date);
        Cache::forever('version-html', $versionHtml);

        $this->info("Version updated to: {$version}");
    }
}

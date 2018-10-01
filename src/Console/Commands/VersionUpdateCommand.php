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

        $tag = trim(shell_exec('git describe --tags'));

        $version = null;
        $versionDate = trim(shell_exec('git log --pretty="%ci" -n1 HEAD'));
        $versionHtml = null;

        if ($tag !== '') {
            preg_match('/(\d+\.\d+\.\d+(?:-\d+)?)(?:-(\d+)(?:\-g([0-9a-f]{8})))?/', $tag, $matches);

            $releaseVersion = $matches[1];

            $version = $tag;

            $versionHtml = '<strong>';
            $versionHtml .= sprintf(
                '<a href="%1$s/releases/tag/%2$s" target="_blank">%2$s</a>',
                static::REPO_URL,
                $releaseVersion
            );

            if (isset($matches[2])) {
                $commitsUponRelease = $matches[2];
                $hash = $matches[3];

                $versionHtml .= sprintf(
                    '-%s-g<a href="%s/compare/%s...%s" target="_blank">%s</a>',
                    $commitsUponRelease,
                    static::REPO_URL,
                    $releaseVersion,
                    $hash,
                    $hash
                );
            }

            $versionHtml .= '</strong>';
        }

        Cache::forever('version', $version);
        Cache::forever('version-date', $versionDate);
        Cache::forever('version-html', $versionHtml);

        $this->info("Version updated to: {$version}");
    }
}

<?php

namespace OpenDominion\Console\Commands\Game;

use Carbon\Carbon;
use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Factories\RoundFactory;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Services\DiscordService;
use RuntimeException;

class RoundOpenCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:round:open
                             {--now : Start the round right now (dev & testing only)}
                             {--days= : Start the round in +DAYS days midnight}
                             {--hours= : Adjust the start time by +/-HOURS, allowing for more fine-tuning}
                             {--league=standard : Round league to use}
                             {--realm-size=8 : Maximum number of dominions in one realm}
                             {--pack-size=5 : Maximum number of players in a pack}
                             {--playersPerRace=2 : Maximum number of players using the same race, 0 = unlimited}
                             {--mixedAlignment=true : Allows for mixed alignments}
                             {--techVersion= : Select which version of the tech system}
                             {--discordGuildId= : Discord guild ID to assign to the round}';

    /** @var string The console command description. */
    protected $description = 'Creates a new round which starts in 3 days';

    /** @var DiscordService */
    protected $discordService;

    /** @var RealmFactory */
    protected $realmFactory;

    /** @var RoundFactory */
    protected $roundFactory;

    /**
     * RoundOpenCommand constructor.
     *
     * @param DiscordService $discordService
     * @param RoundFactory $roundFactory
     * @param RealmFactory $realmFactory
     */
    public function __construct(
        DiscordService $discordService,
        RoundFactory $roundFactory,
        RealmFactory $realmFactory
    )
    {
        parent::__construct();

        $this->discordService = $discordService;
        $this->roundFactory = $roundFactory;
        $this->realmFactory = $realmFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $now = $this->option('now');
        $days = $this->option('days');
        $hours = $this->option('hours');
        $league = $this->option('league');
        $realmSize = $this->option('realm-size');
        $packSize = $this->option('pack-size');
        $playersPerRace = $this->option('playersPerRace');
        $mixedAlignments = $this->option('mixedAlignment');
        $techVersion = $this->option('techVersion');
        $discordGuildId = $this->option('discordGuildId');

        if ($now && (app()->environment() === 'production')) {
            throw new RuntimeException('Option --now may not be used on production');
        }

        if ($realmSize <= 0) {
            throw new RuntimeException('Option --realm-size must be greater than 0.');
        }

        if ($packSize <= 0) {
            throw new RuntimeException('Option --pack-size must be greater than 0.');
        }

        if ($realmSize < $packSize) {
            throw new RuntimeException('Option --realm-size must be greater than or equal to option --packSize.');
        }

        if ($playersPerRace < 0) {
            throw new RuntimeException('Option --playersPerRace must be greater than or equal to 0.');
        }

        // Default to +3 days midnight
        $numDays = 3;
        $numHours = 0;

        if ($days !== null) {
            if (!ctype_digit($days)) {
                throw new RuntimeException('Option --days=DAYS must be an integer');
            }
            $numDays = $days;
        }
        if ($hours !== null) {
            if (!is_numeric($hours)) {
                throw new RuntimeException('Option --hours=HOURS must be an integer');
            }
            $numHours = (int) $hours;
        }

        if ($now) {
            $startDate = 'now';
        } else {
            if ($numHours < 0) {
                $startDate = "{$numDays} days midnight {$numHours} hours";
            } else {
                $startDate = "{$numDays} days midnight +{$numHours} hours";
            }
        }

        $startDate = new Carbon($startDate);

        /** @var RoundLeague $roundLeague */
        $roundLeague = RoundLeague::where('key', $league)->firstOrFail();

        $this->info("Starting a new round in {$roundLeague->key} league");

        $round = $this->roundFactory->create(
            $roundLeague,
            $startDate,
            $realmSize,
            $packSize,
            $playersPerRace,
            $mixedAlignments,
            $techVersion ?? TechHelper::CURRENT_VERSION,
            $discordGuildId
        );

        $this->info("Round {$round->number} created in {$roundLeague->key} league, starting at {$round->start_date}. With a realm size of {$round->realm_size} and a pack size of {$round->pack_size}");
    }
}

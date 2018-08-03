<?php

namespace OpenDominion\Console\Commands\Game;

use Carbon\Carbon;
use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Factories\RoundFactory;
use OpenDominion\Models\RoundLeague;
use RuntimeException;

class RoundOpenCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:round:open
                             {--now : Start the round right now (dev & testing only)}
                             {--open : Start the round in +3 days midnight, allowing for immediate registration}
                             {--days= : Start the round in +DAYS days midnight, allowing for more finetuning}
                             {--league=standard : Round league to use}
                             {--realmSize=12 : Maximum number of dominions in one realm}
                             {--packSize=6 : Maximum number of players in a pack}';

    /** @var string The console command description. */
    protected $description = 'Creates a new round which starts in 5 days';

    /** @var RoundFactory */
    protected $roundFactory;

    /**
     * RoundOpenCommand constructor.
     *
     * @param RoundFactory $roundFactory
     */
    public function __construct(RoundFactory $roundFactory)
    {
        parent::__construct();

        $this->roundFactory = $roundFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $now = $this->option('now');
        $open = $this->option('open');
        $days = $this->option('days');
        $league = $this->option('league');
        $realmSize = $this->option('realmSize');
        $packSize = $this->option('packSize');

        if ($now && (app()->environment() === 'production')) {
            throw new RuntimeException('Option --now may not be used on production');
        }

        if (($now && $open) || ($now && $days) || ($open && $days)) {
            throw new RuntimeException('Options --now, --open and --days are mutually exclusive');
        }

        if ($realmSize <= 0) {
            throw new RuntimeException('Option --realmSize must be greater than 0.');
        }

        if ($packSize <= 0) {
            throw new RuntimeException('Option --packSize must be greater than 0.');
        }

        if ($realmSize < $packSize) {
            throw new RuntimeException('Option --realmSize must be greater than or equal to option --packSize.');
        }

        if ($now) {
            $startDate = 'now';

        } elseif ($open) {
            $startDate = '+3 days midnight';

        } elseif ($days !== null) {
            if (!ctype_digit($days)) {
                throw new RuntimeException('Option --days=DAYS must be an integer');
            }

            $startDate = "+{$days} days midnight";

        } else {
            $startDate = '+5 days midnight';
        }

        $startDate = new Carbon($startDate);
        $roundLeague = RoundLeague::where('key', $league)->firstOrFail();

        $this->info("Starting a new round in {$roundLeague->key} league");

        $round = $this->roundFactory->create($roundLeague, $startDate, $realmSize, $packSize);

        $this->info("Round {$round->number} created in {$roundLeague->key} league, starting at {$round->start_date}. With a realm size of {$round->realm_size} and a pack size of {$round->pack_size}");
    }
}

<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundTickRun;
use OpenDominion\Services\Dominion\RoundMutationService;
use OpenDominion\Services\Dominion\TickService;

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (!app()->environment('testing')) {
    throw new RuntimeException('Concurrency workers only run in the testing environment.');
}

$config = json_decode(getenv('OD_TEST_DATABASE_CONFIG'), true, 512, JSON_THROW_ON_ERROR);
unset($config['name']);
config([
    'database.default' => 'tick_concurrency',
    'database.connections.tick_concurrency' => $config,
    'cache.default' => 'array',
    'session.driver' => 'array',
    'logging.default' => 'null',
]);
DB::purge('tick_concurrency');
Bus::fake();
config(['mail.default' => 'array']);

function announce(string $stage, array $data = []): void
{
    fwrite(STDOUT, json_encode(['stage' => $stage] + $data, JSON_THROW_ON_ERROR) . PHP_EOL);
    fflush(STDOUT);
}

function awaitRelease(): void
{
    $read = [STDIN];
    $write = $except = [];
    if (stream_select($read, $write, $except, 15) !== 1 || trim((string) fgets(STDIN)) !== 'continue') {
        throw new RuntimeException('The concurrency test did not release its worker barrier.');
    }
}

[$script, $mode, $roundId, $dominionId, $hour] = $argv;
Carbon::setTestNow(Carbon::parse($hour));
$round = Round::findOrFail((int) $roundId);
$dominion = Dominion::findOrFail((int) $dominionId);
$initialPlatinum = $dominion->resource_platinum;

if ($mode === 'tick-hold') {
    $app->instance(TickService::class, new class extends TickService {
        protected function applyTickChanges(array $dominionIds): void
        {
            parent::applyTickChanges($dominionIds);
            announce('locked');
            awaitRelease();
        }
    });
}

if ($mode === 'tick-crash-after-commit') {
    Event::listen(Illuminate\Database\Events\TransactionCommitted::class, function ($event) use ($round): void {
        if ($event->connection->transactionLevel() === 0 && RoundTickRun::query()->where('round_id', $round->id)->whereNotNull('completed_at')->exists()) {
            announce('committed');
            exit(73);
        }
    });
}

announce('ready', ['connection_id' => (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id]);
awaitRelease();
announce('attempting');

if (str_starts_with($mode, 'tick')) {
    $applied = app(TickService::class)->performTick($round, null, Carbon::parse($hour));
    announce('done', ['applied' => $applied]);
} elseif (in_array($mode, ['action', 'action-hold'], true)) {
    $observedPlatinum = app(RoundMutationService::class)->runForDominion($dominion, function (Dominion $lockedDominion) use ($mode): int {
        $observedPlatinum = $lockedDominion->resource_platinum;
        if ($mode === 'action-hold') {
            announce('locked');
            awaitRelease();
        }
        $lockedDominion->resource_platinum += 17;
        $lockedDominion->save();
        return $observedPlatinum;
    });
    announce('done', ['initial_platinum' => $initialPlatinum, 'observed_platinum' => $observedPlatinum]);
} else {
    throw new InvalidArgumentException('Unknown concurrency worker mode.');
}

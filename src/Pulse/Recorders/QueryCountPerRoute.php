<?php

namespace OpenDominion\Pulse\Recorders;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Laravel\Pulse\Pulse;
use Symfony\Component\HttpFoundation\Response;

class QueryCountPerRoute
{
    protected int $queryCount = 0;

    public function __construct(
        protected Pulse $pulse,
        protected Repository $config,
    ) {
    }

    public function register(callable $record, Application $app): void
    {
        $app->make(DatabaseManager::class)->listen(function (QueryExecuted $event): void {
            $this->queryCount++;
        });

        $app->make(Kernel::class)->whenRequestLifecycleIsLongerThan(-1, $record);
    }

    public function record(Carbon $startedAt, Request $request, Response $response): void
    {
        $count = $this->queryCount;
        $this->queryCount = 0;

        if (! $request->route() instanceof Route) {
            return;
        }

        if (mt_rand() / mt_getrandmax() > (float) $this->config->get('pulse.recorders.' . self::class . '.sample_rate', 1)) {
            return;
        }

        $path = '/' . ltrim($request->route()->uri(), '/');

        foreach ((array) $this->config->get('pulse.recorders.' . self::class . '.ignore', []) as $pattern) {
            if (preg_match($pattern, $path)) {
                return;
            }
        }

        $this->pulse->record(
            type: 'query_count',
            key: json_encode([$request->method(), $path], flags: JSON_THROW_ON_ERROR),
            value: $count,
            timestamp: $startedAt,
        )->avg()->max()->count();
    }
}

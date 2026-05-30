<?php

namespace OpenDominion\Pulse\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use OpenDominion\Pulse\Recorders\QueryCountPerRoute as QueryCountPerRouteRecorder;

#[Lazy]
class QueryCountPerRoute extends Card
{
    /**
     * @var 'avg'|'max'|'count'
     */
    #[Url(as: 'query-count-per-route')]
    public string $orderBy = 'avg';

    public function render(): Renderable
    {
        [$routes, $time, $runAt] = $this->remember(
            fn () => $this->aggregate(
                'query_count',
                ['avg', 'max', 'count'],
                $this->orderBy,
            )->map(function ($row) {
                [$method, $uri] = json_decode($row->key, flags: JSON_THROW_ON_ERROR);

                return (object) [
                    'method' => $method,
                    'uri' => $uri,
                    'avg' => (float) $row->avg,
                    'max' => (int) $row->max,
                    'count' => (int) $row->count,
                ];
            }),
            $this->orderBy,
        );

        return View::make('pulse::livewire.query-count-per-route', [
            'time' => $time,
            'runAt' => $runAt,
            'routes' => $routes,
            'config' => [
                'sample_rate' => Config::get('pulse.recorders.' . QueryCountPerRouteRecorder::class . '.sample_rate'),
            ],
        ]);
    }
}

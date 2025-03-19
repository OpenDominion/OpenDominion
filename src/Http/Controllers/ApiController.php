<?php

namespace OpenDominion\Http\Controllers;

use Cache;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\User;

class ApiController extends Controller
{
    public function getPbbg()
    {
        return [
            'name' => 'OpenDominion',
            'version' => (Cache::get('version') ?? 'unknown'),
            'description' => 'A text-based, persistent browser-based strategy game (PBBG) in a fantasy war setting',
            'tags' => ['fantasy', 'multiplayer', 'strategy'],
            'status' => 'up',
            'dates' => [
                'born' => '2013-02-04',
                'updated' => (Cache::has('version-date') ? carbon(Cache::get('version-date'))->format('Y-m-d') : null),
            ],
            'players' => [
                'registered' => User::whereActivated(true)->count(),
                'active' => Dominion::whereHas('round', static function ($q) {
                    $q->where('start_date', '<=', now())
                        ->where('end_date', '>', now());
                })->count(),
            ],
            'links' => [
                'game' => 'https://www.opendominion.net',
                'github' => 'https://github.com/OpenDominion/OpenDominion',
            ],
        ];
    }

    public function postBugsnag(Request $request)
    {
        $ip = $request->ip();
        if (!in_array($ip, ['104.196.245.109', '104.196.254.247'])) {
            return ['error' => 'Access denied'];
        }

        try {
            $data = $request->json()->all();

            $message = sprintf(
                '[Error in %s version %s](%s)',
                $data['error']['app']['releaseStage'],
                $data['error']['app']['version'],
                $data['error']['url'],
                $data['error']['requestUrl'],
            );

            $embed = [
                'title' => $data['error']['exceptionClass'],
                'description' => $data['error']['message'],
                'color' => 15548997,
                'fields' => [
                    [
                        'name' => 'Page URL',
                        'value' => $data['error']['requestUrl']
                    ]
                ]
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }

        $webhook = config('app.discord_bugsnag_webhook');
        if ($webhook) {
            $client = new Client();
            $response = $client->post($webhook, ['json' => [
                'content' => $message,
                'embeds' => [$embed],
            ]]);
        }
        if (!$webhook || $response->getStatusCode() != 204) {
            return ['error' => 'Failed to send to Discord'];
        }
        return ['success' => true];
    }
}

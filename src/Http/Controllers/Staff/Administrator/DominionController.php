<?php

namespace OpenDominion\Http\Controllers\Staff\Administrator;

use Carbon\Carbon;
use DateInterval;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;

class DominionController extends AbstractController
{
    public function index(Request $request)
    {
        $rounds = Round::all()->sortByDesc('start_date');

        $selectedRound = $request->input('round');
        if ($selectedRound) {
            $round = Round::findOrFail($selectedRound);
        } else {
            $round = $rounds->first();
        }

        $dominions = Dominion::with([
            'queues',
            'race',
            'race.perks',
            'race.units',
            'race.units.perks',
            'techs',
            'techs.perks',
            'user'
        ])->where('round_id', $round->id)->get();

        return view('pages.staff.administrator.dominions.index', [
            'round' => $round,
            'rounds' => $rounds,
            'dominions' => $dominions,
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
        ]);
    }

    public function show(Dominion $dominion)
    {
        return view('pages.staff.administrator.dominions.show', [
            'dominion' => $dominion,
            'resourceData' => $this->getResourceData($dominion, 3),
        ]);
    }

    protected function getResourceData(Dominion $dominion, int $days): array
    {
        $data = [
            'labels' => $this->getLabelData($days),
            'datasets' => [],
        ];

        $resourceTypes = [
            'platinum' => '#e5e4e2',
            'food' => '#bab742',
            'lumber' => '#966f33',
            'mana' => '#8cd1e5',
            'ore' => '#dd5541',
            'gems' => '#d24294',
            'tech' => '#51d287',
            'boats' => '#5867d2',
        ];

        $history = $dominion->history()
            ->where('created_at', '>=', new Carbon('-3 days midnight'))
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($resourceTypes as $type => $color) {
            $dataset = $this->getDataset(ucfirst($type), $color);
            $value = $dominion->{'resource_' . $type};
            $date = null;

//            $dataset['data'][] = [
//                'x' => (new Carbon('next hour'))->format('Y-m-d H:00'),
//                'y' => $dominion->{'resource_' . $type},
//            ];

            foreach ($history as $row) {
                $delta = $row->delta;

                if (!isset($delta['resource_' . $type])) {
                    continue;
                }

                $value -= $delta['resource_' . $type];

                if (($date === null) || ($date->hour !== $row->created_at->hour)) {
                    $date = $row->created_at;

                    $dataset['data'][] = [
                        'x' => $row->created_at->format('Y-m-d H:i'),
                        'y' => $value,
                    ];
                }
            }

            $dataset['data'] = array_reverse($dataset['data']);
//            array_pop($dataset['data']);

            $data['datasets'][] = $dataset;
        }

        return $data;
    }

    protected function getLabelData(int $days): array
    {
        $endDate = new Carbon('tomorrow midnight');
        $beginDate = (clone $endDate)->addDays(-$days);
        $interval = new DateInterval('P1D');
        $dateRange = new \DatePeriod($beginDate, $interval, $endDate);

        $data = [];

        foreach ($dateRange as $date) {
            $data[] = $date->format('Y-m-d');
        }

        return $data;
    }

    protected function getDataset(string $label, string $color): array
    {
        list($r, $g, $b) = sscanf($color, '#%02x%02x%02x');

        return [
            'label' => $label,
            'backgroundColor' => "rgba($r, $g, $b, 0.5)",
            'borderColor' => "rgba($r, $g, $b, 0.8)",
            'fill' => false,
            'lineTension' => 0,
            'data' => [],
        ];
    }
}

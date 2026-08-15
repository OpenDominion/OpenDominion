<?php

namespace OpenDominion\Calculators;

use Illuminate\Database\Eloquent\Collection;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\DailyRanking;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Round;
use OpenDominion\Models\Valor;

class ValorCalculator
{
    protected const FIXED_VALOR_LAND_RANK = 6000;
    protected const FIXED_VALOR_LAND_TOTAL = 3000;
    protected const FIXED_VALOR_LAND_CONQUERED = 1500;
    protected const FIXED_VALOR_BOUNTIES = 1500;

    /**
     * The only ranking keys fixed valor reads.
     *
     * @var array<int, string>
     */
    protected const FIXED_VALOR_KEYS = [
        'largest-dominions',
        'total-land-conquered',
        'bounties-collected',
    ];

    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * ValorCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     */
    public function __construct(
        LandCalculator $landCalculator
    )
    {
        $this->landCalculator = $landCalculator;
    }

    public function calculate(Round $round)
    {
        $valor = [
            'dominions' => [],
            'realms' => [],
        ];

        $dominions = $round->activeDominions()
            ->human()
            ->where('protection_finished', true)
            ->get();
        $fixedValor = $this->calculateFixedValor($round, $dominions);
        $bonusValor = $this->calculateBonusValor($round, $dominions);

        $realms = $round->realms->where('number', '!=', 0);
        foreach ($realms as $realm) {
            $valor['realms'][$realm->id] = 0;
            foreach ($realm->dominions as $dominion) {
                $individualValor = (
                    (isset($fixedValor[$dominion->id]) ? $fixedValor[$dominion->id] : 0) +
                    (isset($bonusValor[$dominion->id]) ? $bonusValor[$dominion->id] : 0)
                );
                $valor['dominions'][$dominion->id] = $individualValor;
                $valor['realms'][$realm->id] += $individualValor;
            }
        }

        return $valor;
    }

    public function calculateBreakdown(Round $round, int $realmId): array
    {
        $dominions = $round->activeDominions()
            ->human()
            ->where('protection_finished', true)
            ->where('realm_id', $realmId)
            ->get();

        $dominionIds = $dominions->pluck('id');

        // Get round-wide totals via aggregate queries
        $totals = DailyRanking::query()
            ->where('round_id', $round->id)
            ->where('realm_number', '!=', 0)
            ->whereIn('key', ['largest-dominions', 'total-land-conquered', 'bounties-collected'])
            ->selectRaw('`key`, SUM(`value`) as total')
            ->groupBy('key')
            ->pluck('total', 'key');
        $totalLand = $totals->get('largest-dominions', 0);
        $totalLandConquered = $totals->get('total-land-conquered', 0);
        $totalBounties = $totals->get('bounties-collected', 0);

        // Only fetch rankings for this realm's dominions
        $rankings = DailyRanking::query()
            ->where('round_id', $round->id)
            ->whereIn('dominion_id', $dominionIds)
            ->whereIn('key', ['largest-dominions', 'total-land-conquered', 'bounties-collected'])
            ->get();

        $valor = Valor::where('round_id', $round->id)
            ->whereIn('dominion_id', $dominionIds)
            ->get();

        $breakdown = [];
        foreach ($dominions as $dominion) {
            $domRankings = $rankings->where('dominion_id', $dominion->id);
            $landRank = $domRankings->where('key', 'largest-dominions')->pluck('rank')->first() ?? 0;
            $landConquered = $domRankings->where('key', 'total-land-conquered')->pluck('value')->first() ?? 0;
            $bounties = $domRankings->where('key', 'bounties-collected')->pluck('value')->first() ?? 0;

            $landRankValor = $this->getFixedValorLandRank($landRank);
            $totalLandValor = $this->getFixedValorTotalLand($dominion, $totalLand);
            $conqueredValor = $this->getFixedValorTotalConquered($landConquered, $totalLandConquered);
            $bountiesValor = $this->getFixedValorBounties($bounties, $totalBounties);

            $domValor = $valor->where('dominion_id', $dominion->id);
            $warHitValor = $domValor->where('source', 'war_hit')->sum('amount');
            $largestHitValor = $domValor->where('source', 'largest_hit')->sum('amount');
            $wonderValor = $domValor->whereIn('source', ['wonder', 'wonder_neutral'])->sum('amount');

            $total = $landRankValor + $totalLandValor + $conqueredValor + $bountiesValor + $warHitValor + $largestHitValor + $wonderValor;

            $breakdown[] = [
                'dominion' => $dominion,
                'land_rank' => $landRankValor,
                'total_land' => $totalLandValor,
                'land_conquered' => $conqueredValor,
                'bounties' => $bountiesValor,
                'war_hits' => $warHitValor,
                'largest_hits' => $largestHitValor,
                'wonders' => $wonderValor,
                'total' => $total,
            ];
        }

        usort($breakdown, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return $breakdown;
    }

    public function calculateFixedValor(Round $round, Collection $dominions)
    {
        // Totals span the whole round, so they are aggregated in SQL rather
        // than summed over a hydrated collection of every ranking row.
        $totals = DailyRanking::query()
            ->where('round_id', $round->id)
            ->where('realm_number', '!=', 0)
            ->whereIn('key', self::FIXED_VALOR_KEYS)
            ->selectRaw('`key`, SUM(`value`) as total')
            ->groupBy('key')
            ->pluck('total', 'key');

        $totalLand = (int)$totals->get('largest-dominions', 0);
        $totalLandConquered = (int)$totals->get('total-land-conquered', 0);
        $totalBounties = (int)$totals->get('bounties-collected', 0);

        // The realm_number filter here is deliberate and differs from
        // calculateBreakdown(), which fetches its per-dominion rows unfiltered.
        // A dominion whose rows were recorded in realm 0 has always scored
        // nothing for land rank, land conquered and bounties. Do not "align"
        // the two methods - ValorCalculatorTest pins this.
        //
        // Indexed once by dominion and key, instead of re-filtering the whole
        // collection inside the loop below.
        $rankings = DailyRanking::query()
            ->where('round_id', $round->id)
            ->where('realm_number', '!=', 0)
            ->whereIn('dominion_id', $dominions->pluck('id'))
            ->whereIn('key', self::FIXED_VALOR_KEYS)
            ->get(['dominion_id', 'key', 'rank', 'value'])
            ->groupBy('dominion_id')
            ->map(static function ($rows) {
                return $rows->keyBy('key');
            });

        $fixedValor = [];
        foreach ($dominions as $dominion) {
            if (!isset($fixedValor[$dominion->id])) {
                $fixedValor[$dominion->id] = 0;
            }
            $totalValor = 0;
            $domRankings = $rankings->get($dominion->id);
            // ?? 0 covers both a missing row and a NULL rank, which is what the
            // previous ->pluck('rank')->first() ?? 0 did.
            $landRank = $domRankings?->get('largest-dominions')?->rank ?? 0;
            $landConquered = $domRankings?->get('total-land-conquered')?->value ?? 0;
            $bounties = $domRankings?->get('bounties-collected')?->value ?? 0;

            $totalValor += $this->getFixedValorLandRank($landRank);
            // TODO: Pass in land total instead?
            $totalValor += $this->getFixedValorTotalLand($dominion, $totalLand);
            $totalValor += $this->getFixedValorTotalConquered($landConquered, $totalLandConquered);
            $totalValor += $this->getFixedValorBounties($bounties, $totalBounties);
            $fixedValor[$dominion->id] += $totalValor;
        }

        return $fixedValor;
    }

    protected function getFixedValorLandRank(int $landRank): float
    {
        if ($landRank == 0) {
            return 0;
        } elseif ($landRank == 1) {
            return 1000;
        } elseif ($landRank == 2) {
            return 500;
        }

        // The sum of this series (3rd through 277th) is ~4513
        // Adding 1500 for 1st/2nd is equal to 6000 (FIXED_VALOR_LAND_RANK)
        return max(0, round(1250 / $landRank) - 5);
    }

    protected function getFixedValorTotalLand(Dominion $dominion, int $totalLand): float
    {
        if ($totalLand == 0) {
            return 0;
        }

        $valorPerAcre = $this::FIXED_VALOR_LAND_TOTAL / $totalLand;
        $acres = $this->landCalculator->getTotalLand($dominion);

        return round($valorPerAcre * $acres, 2);
    }

    protected function getFixedValorTotalConquered(int $landConquered, int $totalLandConquered): float
    {
        if ($totalLandConquered == 0) {
            return 0;
        }

        $valorPerAcre = $this::FIXED_VALOR_LAND_CONQUERED / $totalLandConquered;

        return round($valorPerAcre * $landConquered, 2);
    }

    protected function getFixedValorBounties(int $bounties, int $totalBounties): float
    {
        if ($totalBounties == 0) {
            return 0;
        }

        $valorPerBounty = $this::FIXED_VALOR_BOUNTIES / $totalBounties;

        return round($valorPerBounty * $bounties, 2);
    }

    public function calculateBonusValor(Round $round, Collection $dominions)
    {
        // Summed per dominion in SQL rather than by re-filtering a collection
        // of the round's entire valor history once per dominion.
        $totals = Valor::query()
            ->where('round_id', $round->id)
            ->whereIn('dominion_id', $dominions->pluck('id'))
            ->selectRaw('dominion_id, SUM(amount) as total')
            ->groupBy('dominion_id')
            ->pluck('total', 'dominion_id');

        $bonusValor = [];
        foreach ($dominions as $dominion) {
            $bonusValor[$dominion->id] = (float)$totals->get($dominion->id, 0);
        }

        return $bonusValor;
    }
}

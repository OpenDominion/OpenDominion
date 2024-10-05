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
            ->where('user_id', '!=', null)
            ->where('protection_ticks_remaining', 0)
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

    public function calculateFixedValor(Round $round, Collection $dominions)
    {
        $rankings = DailyRanking::query()
            ->where('round_id', $round->id)
            ->where('realm_number', '!=', 0)
            ->get();
        $totalLand = $rankings->where('key', 'largest-dominions')->sum('value');
        $totalLandConquered = $rankings->where('key', 'total-land-conquered')->sum('value');
        $totalBounties = $rankings->where('key', 'bounties-collected')->sum('value');

        $fixedValor = [];
        foreach ($dominions as $dominion) {
            if (!isset($fixedValor[$dominion->id])) {
                $fixedValor[$dominion->id] = 0;
            }
            $totalValor = 0;
            $domRankings = $rankings->where('dominion_id', $dominion->id);
            $landRank = $domRankings->where('key', 'largest-dominions')->pluck('rank')->first() ?? 0;
            $landConquered = $domRankings->where('key', 'total-land-conquered')->pluck('value')->first() ?? 0;
            $bounties = $domRankings->where('key', 'bounties-collected')->pluck('value')->first() ?? 0;

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
        } else if ($landRank == 1) {
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
        $valor = Valor::where('round_id', $round->id)->get();

        $bonusValor = [];
        foreach ($dominions as $dominion) {
            $individualValor = $valor->where('dominion_id', $dominion->id)->sum('amount');
            $bonusValor[$dominion->id] = $individualValor;
        }

        return $bonusValor;
    }
}

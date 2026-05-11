<?php

namespace OpenDominion\Helpers;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Valuable;
use RuntimeException;

class ValuablesHelper
{
    public const PASSIVE_DISCOVERY_CHANCE       = 0.01;
    public const SPY_OP_DISCOVERY_CHANCE        = 0.10;
    public const SPY_OP_STRENGTH_COST           = 5.0;
    public const EXPIRATION_HOURS               = 48;
    public const MIN_INVESTIGATION_HOURS        = 36;
    public const MAX_INVESTIGATION_HOURS        = 6;
    public const INVESTIGATION_HOUR_STEP        = 6;
    public const SPY_STRENGTH_PER_INVESTIGATION = 2.0;

    public const TYPES = ['relic', 'jewelry', 'artwork', 'equipment', 'text'];

    public const RARITY_COMMON    = 'common';
    public const RARITY_UNCOMMON  = 'uncommon';
    public const RARITY_RARE      = 'rare';
    public const RARITY_EPIC      = 'epic';
    public const RARITY_LEGENDARY = 'legendary';

    /** @var array|null cached parsed names file */
    protected $namesCache = null;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
    }

    /**
     * Returns the rarity configuration table indexed by rarity key.
     */
    public static function getRarityConfig(): array
    {
        return [
            self::RARITY_COMMON => [
                'label'                => 'Common',
                'spy_hours_multiplier' => 0.5,
                'base_value_min'       => 5000,
                'base_value_max'       => 10000,
                'transfer_price'       => 2500,
            ],
            self::RARITY_UNCOMMON => [
                'label'                => 'Uncommon',
                'spy_hours_multiplier' => 1.0,
                'base_value_min'       => 10000,
                'base_value_max'       => 25000,
                'transfer_price'       => 5000,
            ],
            self::RARITY_RARE => [
                'label'                => 'Rare',
                'spy_hours_multiplier' => 2.0,
                'base_value_min'       => 25000,
                'base_value_max'       => 50000,
                'transfer_price'       => 10000,
            ],
            self::RARITY_EPIC => [
                'label'                => 'Epic',
                'spy_hours_multiplier' => 3.0,
                'base_value_min'       => 50000,
                'base_value_max'       => 100000,
                'transfer_price'       => 20000,
            ],
            self::RARITY_LEGENDARY => [
                'label'                => 'Legendary',
                'spy_hours_multiplier' => 5.0,
                'base_value_min'       => 100000,
                'base_value_max'       => 250000,
                'transfer_price'       => 40000,
            ],
        ];
    }

    /**
     * Selects rarity for a discovery based on target's defensive spy ratio
     * plus target land. Better-defended targets yield rarer valuables.
     */
    public function selectRarity(Dominion $attacker, Dominion $target): string
    {
        $targetLand = $this->landCalculator->getTotalLand($target);
        $landScore = max(0.0, min(1.0, ($targetLand - 500) / 7500));
        $spyScore  = max(0.0, min(1.0, $this->militaryCalculator->getSpyRatio($target, 'defense')));
        $score = ($landScore + $spyScore) / 2;
        $rarityIndex = (int) round($score * 4);

        $rarities = array_keys(self::getRarityConfig());
        return $rarities[$rarityIndex] ?? self::RARITY_COMMON;
    }

    /**
     * Generates a name for a valuable of the given type and rarity.
     */
    public function generateName(string $type, string $rarity): string
    {
        $names = $this->loadNames();

        if (!isset($names['types'][$type])) {
            throw new RuntimeException("Unknown valuable type: {$type}");
        }

        $pools = $names['types'][$type];

        // Epic / Legendary: pick from a curated flat list.
        if ($rarity === self::RARITY_EPIC || $rarity === self::RARITY_LEGENDARY) {
            $list = $pools[$rarity] ?? [];
            if (empty($list)) {
                throw new RuntimeException("No {$rarity} names for type {$type}");
            }
            return $list[array_rand($list)];
        }

        $patterns = $this->patternsForRarity($rarity);
        $pattern = $patterns[array_rand($patterns)];

        return $this->fillPattern($pattern, $pools);
    }

    /**
     * Discovery message phrase ("a Rare relic", "a Legendary work of art", ...).
     */
    public function discoveryPhrase(string $rarity, string $type): string
    {
        $rarityLabel = self::getRarityConfig()[$rarity]['label'] ?? ucfirst($rarity);

        switch ($type) {
            case 'jewelry':
                $noun = 'item of jewelry';
                break;
            case 'equipment':
                $noun = 'piece of equipment';
                break;
            case 'artwork':
                $noun = 'work of art';
                break;
            case 'relic':
            case 'text':
            default:
                $noun = $type;
                break;
        }

        $article = preg_match('/^[aeiouAEIOU]/', $rarityLabel) ? 'an' : 'a';

        return "{$article} {$rarityLabel} {$noun}";
    }

    /**
     * Returns the flat realm-transfer price for a valuable's rarity.
     */
    public function getTransferPrice(Valuable $valuable): int
    {
        return (int) self::getRarityConfig()[$valuable->rarity]['transfer_price'];
    }

    /**
     * Returns the current sale price (decays from max to min over EXPIRATION_HOURS after stolen_at).
     */
    public function getCurrentSalePrice(Valuable $valuable): int
    {
        $config = self::getRarityConfig()[$valuable->rarity];

        if ($valuable->stolen_at === null) {
            return (int) $config['base_value_max'];
        }

        $elapsedHours = $valuable->stolen_at->diffInSeconds(now()) / 3600;
        $t = max(0.0, min($elapsedHours / self::EXPIRATION_HOURS, 1.0));

        $price = $config['base_value_max'] - ($config['base_value_max'] - $config['base_value_min']) * $t;

        return (int) round($price);
    }

    /**
     * Returns 0–100 progress for an in-flight investigation. Returns 0 if the
     * valuable is not currently investigating.
     */
    public function getInvestigationProgress(Valuable $valuable): float
    {
        if ($valuable->investigation_started_at === null || $valuable->investigation_ends_at === null) {
            return 0.0;
        }

        $elapsed = max(0, $valuable->investigation_started_at->diffInSeconds(now()));
        $total = max(1, $valuable->investigation_started_at->diffInSeconds($valuable->investigation_ends_at));

        return min(100.0, ($elapsed / $total) * 100);
    }

    /**
     * Returns the Bootstrap text-color class corresponding to a 0–100 progress
     * value. Shifts red → yellow → blue → green at 25/50/75%.
     */
    public function getInvestigationProgressColorClass(float $progressPct): string
    {
        if ($progressPct < 25) {
            return 'text-danger';
        }
        if ($progressPct < 50) {
            return 'text-warning';
        }
        if ($progressPct < 75) {
            return 'text-primary';
        }
        return 'text-success';
    }

    /**
     * Returns the absolute spy count for a dominion (military_spies + 2*assassins + counts_as_spy units).
     */
    public function getSpyCount(Dominion $dominion): int
    {
        $spies = $dominion->military_spies + ($dominion->military_assassins * 2);

        foreach ($dominion->race->units as $unit) {
            if ($unit->getPerkValue('counts_as_spy')) {
                $spies += rfloor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy'));
            }
        }

        return (int) $spies;
    }

    /**
     * Returns the number of spies committed to active investigations for this dominion.
     */
    public function getSpiesCommitted(Dominion $dominion): int
    {
        return (int) Valuable::query()
            ->where('source_dominion_id', $dominion->id)
            ->where('status', Valuable::STATUS_INVESTIGATING)
            ->sum('spies_assigned');
    }

    /**
     * Returns the number of spies the dominion can newly assign to an investigation.
     */
    public function getAvailableSpies(Dominion $dominion): int
    {
        return $this->getSpyCount($dominion) - $this->getSpiesCommitted($dominion);
    }

    /**
     * Loads (and caches) the JSON name pools.
     */
    protected function loadNames(): array
    {
        if ($this->namesCache !== null) {
            return $this->namesCache;
        }

        $path = base_path('app/data/valuables.json');
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException("Unable to read valuables.json at {$path}");
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('valuables.json could not be parsed.');
        }

        return $this->namesCache = $decoded;
    }

    /**
     * Returns the list of naming patterns available for a given rarity.
     * Each pattern is an array of segments — strings are literals, '@xxx' refers to a slot pool key.
     */
    protected function patternsForRarity(string $rarity): array
    {
        $adjBase     = ['@adjectives', ' ', '@bases'];
        $matBase     = ['@materials', ' ', '@bases'];
        $theAdjBase  = ['The ', '@adjectives', ' ', '@bases'];
        $baseOfPlace = ['@bases', ' of ', '@places'];
        $possBase    = ['@possessives', ' ', '@bases'];
        $baseOfEvent = ['@bases', ' of the ', '@events'];

        switch ($rarity) {
            case self::RARITY_COMMON:
                return [$adjBase, $matBase];
            case self::RARITY_UNCOMMON:
                return [$adjBase, $matBase, $theAdjBase];
            case self::RARITY_RARE:
            default:
                return [$adjBase, $matBase, $theAdjBase, $baseOfPlace, $possBase, $baseOfEvent];
        }
    }

    /**
     * Fills a pattern by replacing slot tokens with random picks from the corresponding pool.
     */
    protected function fillPattern(array $pattern, array $pools): string
    {
        $out = '';
        foreach ($pattern as $segment) {
            if (is_string($segment) && strlen($segment) > 0 && $segment[0] === '@') {
                $key = substr($segment, 1);
                $pool = $pools[$key] ?? [];
                if (empty($pool)) {
                    throw new RuntimeException("Empty word pool for key {$key}");
                }
                $out .= $pool[array_rand($pool)];
            } else {
                $out .= $segment;
            }
        }

        return $out;
    }
}

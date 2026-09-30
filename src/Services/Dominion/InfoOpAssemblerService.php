<?php

namespace OpenDominion\Services\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;

/**
 * Assembles the per-target info-op payload for the API. Each op is formatted
 * like the web "Copy Ops" export built inline in
 * app/resources/views/pages/dominion/op-center/show.blade.php, but keyed by
 * the stored info op type (clear_sight, barracks_spy) where Copy Ops uses
 * short names (status, barracks).
 */
class InfoOpAssemblerService
{
    /**
     * Info op types exposed, keyed in the output by their stored type (the
     * spell and espionage keys). Ordered as in the Copy Ops export.
     */
    private const TYPES = [
        'clear_sight',
        'revelation',
        'castle_spy',
        'barracks_spy',
        'survey_dominion',
        'land_spy',
        'vision',
        'disclosure',
    ];

    public function __construct(private SpellHelper $spellHelper)
    {
    }

    /**
     * Every op type is present in the result; types with no info op are null.
     *
     * @param Collection $latestInfoOps  Collection of InfoOp records for a single target.
     * @return array<string, array<string, mixed>|null>
     */
    public function assembleForTarget(Dominion $target, Collection $latestInfoOps): array
    {
        $ops = array_fill_keys(self::TYPES, null);

        foreach (self::TYPES as $type) {
            $infoOp = $latestInfoOps->firstWhere('type', $type);
            if ($infoOp === null) {
                continue;
            }

            $ops[$type] = $this->buildEntry($type, $infoOp, $target);
        }

        if (isset($ops['revelation'])) {
            $obfuscated = $this->spellHelper->obfuscateInfoOps(['revelation' => $ops['revelation']]);
            $ops['revelation'] = $obfuscated['revelation'];
        }

        return $ops;
    }

    /**
     * Assembles every given info op of one type, in the order provided, using
     * the same per-op format as assembleForTarget().
     *
     * @param string $type  Info op type, e.g. "barracks_spy".
     * @param Collection $infoOps  InfoOp records of that type for a single target.
     * @return array<int, array<string, mixed>>
     */
    public function assembleHistory(Dominion $target, string $type, Collection $infoOps): array
    {
        if (!$this->isValidType($type)) {
            return [];
        }

        return $infoOps
            ->map(function ($infoOp) use ($type, $target) {
                $entry = $this->buildEntry($type, $infoOp, $target);

                if ($type === 'revelation') {
                    $entry = $this->spellHelper->obfuscateInfoOps(['revelation' => $entry])['revelation'];
                }

                return $entry;
            })
            ->values()
            ->all();
    }

    /**
     * @return string[]
     */
    public function getTypes(): array
    {
        return self::TYPES;
    }

    public function isValidType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    private function buildEntry(string $type, $infoOp, Dominion $target): array
    {
        $createdAt = $infoOp->created_at?->toIso8601ZuluString();

        if ($type === 'revelation') {
            return [
                'spells' => $infoOp->data,
                'created_at' => $createdAt,
            ];
        }

        $entry = is_array($infoOp->data) ? $infoOp->data : [];

        if ($type === 'clear_sight') {
            $entry['race_name'] = $target->race->name;
            $entry['realm'] = $target->realm->number;
            $entry['name'] = $target->name;
            unset($entry['race_id']);
        }

        $entry['created_at'] = $createdAt;

        return $entry;
    }
}

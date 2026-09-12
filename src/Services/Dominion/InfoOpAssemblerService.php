<?php

namespace OpenDominion\Services\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;

/**
 * Assembles the per-target info-op payload that the web "Copy Ops" feature
 * exposes on the Op Center show page. Mirrors the inline `$infoOps` build in
 * app/resources/views/pages/dominion/op-center/show.blade.php.
 */
class InfoOpAssemblerService
{
    private const TYPE_MAP = [
        'clear_sight' => 'status',
        'revelation' => 'revelation',
        'castle_spy' => 'castle',
        'barracks_spy' => 'barracks',
        'survey_dominion' => 'survey',
        'land_spy' => 'land',
        'vision' => 'vision',
        'disclosure' => 'disclosure',
    ];

    public function __construct(private SpellHelper $spellHelper)
    {
    }

    /**
     * @param Collection $latestInfoOps  Collection of InfoOp records for a single target.
     */
    public function assembleForTarget(Dominion $target, Collection $latestInfoOps): array
    {
        $ops = [];

        foreach (self::TYPE_MAP as $type => $key) {
            $infoOp = $latestInfoOps->firstWhere('type', $type);
            if ($infoOp === null) {
                continue;
            }

            $ops[$key] = $this->buildEntry($type, $infoOp, $target);
        }

        if (isset($ops['revelation'])) {
            $obfuscated = $this->spellHelper->obfuscateInfoOps(['revelation' => $ops['revelation']]);
            $ops['revelation'] = $obfuscated['revelation'];
        }

        return $ops;
    }

    private function buildEntry(string $type, $infoOp, Dominion $target): array
    {
        $createdAt = $infoOp->created_at?->toIso8601String();

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

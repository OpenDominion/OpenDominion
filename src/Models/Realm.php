<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;
use OpenDominion\Services\Realm\HistoryService;

/**
 * OpenDominion\Models\Realm
 *
 * @property int $id
 * @property int $round_id
 * @property int|null $monarch_dominion_id
 * @property int|null $general_dominion_id
 * @property int|null $spymaster_dominion_id
 * @property int|null $magister_dominion_id
 * @property int|null $mage_dominion_id
 * @property int|null $jester_dominion_id
 * @property string $alignment
 * @property int $number
 * @property string|null $name
 * @property int|null $discord_role_id
 * @property int|null $discord_category_id
 * @property string|null $motd
 * @property \Illuminate\Support\Carbon|null $motd_updated_at
 * @property int $rating
 * @property int $valor
 * @property array|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Council\Thread[] $councilThreads
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion[] $dominions
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion[] $infoOpTargetDominions
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\InfoOp[] $infoOps
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Pack[] $packs
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\RealmWar[] $warsIncoming
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\RealmWar[] $warsOutgoing
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\RoundWonder[] $roundWonders
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Wonder[] $wonders
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Realm\History[] $history
 * @property-read \OpenDominion\Models\Dominion $monarch
 * @property-read \OpenDominion\Models\Dominion $general
 * @property-read \OpenDominion\Models\Dominion $spymaster
 * @property-read \OpenDominion\Models\Dominion $magister
 * @property-read \OpenDominion\Models\Dominion $mage
 * @property-read \OpenDominion\Models\Dominion $jester
 * @property-read \OpenDominion\Models\Round $round
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Realm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Realm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Realm query()
 * @mixin \Eloquent
 */
class Realm extends AbstractModel
{
    protected $casts = [
        'settings' => 'array',
    ];

    public function councilThreads()
    {
        return $this->hasMany(Council\Thread::class);
    }

    public function dominions()
    {
        return $this->hasMany(Dominion::class);
    }

    public function history()
    {
        return $this->hasMany(Realm\History::class);
    }

    public function infoOps()
    {
        return $this->hasMany(InfoOp::class, 'source_realm_id');
    }

    public function infoOpTargetDominions()
    {
        return $this->hasManyThrough(
            Dominion::class,
            InfoOp::class,
            'source_realm_id',
            'id',
            null,
            'target_dominion_id'
        )
            ->groupBy('target_dominion_id')
            ->orderBy('info_ops.created_at', 'desc');
    }

    public function monarch()
    {
        return $this->hasOne(Dominion::class, 'id', 'monarch_dominion_id');
    }

    public function general()
    {
        return $this->hasOne(Dominion::class, 'id', 'general_dominion_id');
    }

    public function spymaster()
    {
        return $this->hasOne(Dominion::class, 'id', 'spymaster_dominion_id');
    }

    public function magister()
    {
        return $this->hasOne(Dominion::class, 'id', 'magister_dominion_id');
    }

    public function mage()
    {
        return $this->hasOne(Dominion::class, 'id', 'mage_dominion_id');
    }

    public function jester()
    {
        return $this->hasOne(Dominion::class, 'id', 'jester_dominion_id');
    }

    public function packs()
    {
        return $this->hasMany(Pack::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function roundWonders()
    {
        return $this->hasMany(RoundWonder::class);
    }

    public function warsIncoming()
    {
        return $this->hasMany(RealmWar::class, 'target_realm_id');
    }

    public function warsOutgoing()
    {
        return $this->hasMany(RealmWar::class, 'source_realm_id');
    }

    public function wonders()
    {
        return $this->belongsToMany(
            Wonder::class,
            'round_wonders',
            'realm_id',
            'wonder_id'
        )
            ->withTimestamps()
            ->withPivot('realm_id', 'power');
    }

    // Eloquent Query Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('number', '!=', 0);
    }

    // Methods

    public function totalPackSize(): int
    {
        return $this->packs->sum(function ($pack) {
            return $pack->sizeAllocated();
        });
    }

    public function sizeAllocated(): int
    {
        return $this->packs->sum(function ($pack) {
            return $pack->remainingSlots();
        }) + $this->dominions->count();
    }

    public function hasWonder(string $wonderKey): bool
    {
        return $this->wonders->keyBy('key')->has($wonderKey);
    }

    public function getSetting(string $key)
    {
        if (!array_has($this->settings, $key)) {
            return null;
        }

        return array_get($this->settings, $key);
    }

    // todo: move to eloquent events, see $dispatchesEvents
    public function save(array $options = [])
    {
        $recordChanges = isset($options['event']);

        if ($recordChanges) {
            $realmHistoryService = app(HistoryService::class);
            $deltaAttributes = $realmHistoryService->getDeltaAttributes($this);
        }

        $saved = parent::save($options);

        if ($saved && $recordChanges) {
            $extraAttributes = [
                'monarch_dominion_id',
                'general_dominion_id',
                'spymaster_dominion_id',
                'magister_dominion_id',
                'mage_dominion_id',
                'jester_dominion_id',
                'war_id'
            ];
            foreach ($extraAttributes as $attr) {
                if (isset($options[$attr])) {
                    $deltaAttributes[$attr] = $options[$attr];
                }
            }
            /** @noinspection PhpUndefinedVariableInspection */
            $realmHistoryService->record($this, $deltaAttributes, $options['event']);
        }

        return $saved;
    }
}

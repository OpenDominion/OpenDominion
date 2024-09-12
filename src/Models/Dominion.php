<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;
use OpenDominion\Events\DominionSavedEvent;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\SelectorService;

/**
 * OpenDominion\Models\Dominion
 *
 * @property int $id
 * @property int $user_id
 * @property int $round_id
 * @property int|null $pack_id
 * @property int $realm_id
 * @property int $race_id
 * @property string $name
 * @property string|null $ruler_name
 * @property int $prestige
 * @property int $peasants
 * @property int $peasants_last_hour
 * @property int $draft_rate
 * @property int $morale
 * @property float $spy_strength
 * @property float $wizard_strength
 * @property int $spy_mastery
 * @property int $wizard_mastery
 * @property int $resilience
 * @property int $fireball_meter
 * @property int $lightning_bolt_meter
 * @property bool $daily_platinum
 * @property bool $daily_land
 * @property int $daily_actions
 * @property int $resource_platinum
 * @property int $resource_food
 * @property int $resource_lumber
 * @property int $resource_mana
 * @property int $resource_ore
 * @property int $resource_gems
 * @property int $resource_tech
 * @property float $resource_boats
 * @property int $improvement_science
 * @property int $improvement_keep
 * @property int $improvement_spires
 * @property int $improvement_forges
 * @property int $improvement_walls
 * @property int $improvement_harbor
 * @property int $military_draftees
 * @property int $military_unit1
 * @property int $military_unit2
 * @property int $military_unit3
 * @property int $military_unit4
 * @property int $military_spies
 * @property int $military_assassins
 * @property int $military_wizards
 * @property int $military_archmages
 * @property int $land_plain
 * @property int $land_mountain
 * @property int $land_swamp
 * @property int $land_cavern
 * @property int $land_forest
 * @property int $land_hill
 * @property int $land_water
 * @property int $discounted_land
 * @property int $building_home
 * @property int $building_alchemy
 * @property int $building_farm
 * @property int $building_smithy
 * @property int $building_masonry
 * @property int $building_ore_mine
 * @property int $building_gryphon_nest
 * @property int $building_tower
 * @property int $building_wizard_guild
 * @property int $building_temple
 * @property int $building_diamond_mine
 * @property int $building_school
 * @property int $building_lumberyard
 * @property int $building_forest_haven
 * @property int $building_factory
 * @property int $building_guard_tower
 * @property int $building_shrine
 * @property int $building_barracks
 * @property int $building_dock
 * @property int $calculated_networth
 * @property \Illuminate\Support\Carbon|null $council_last_read
 * @property \Illuminate\Support\Carbon|null $forum_last_read
 * @property \Illuminate\Support\Carbon|null $town_crier_last_seen
 * @property \Illuminate\Support\Carbon|null $wonders_last_seen
 * @property \Illuminate\Support\Carbon|null $royal_guard_active_at
 * @property \Illuminate\Support\Carbon|null $elite_guard_active_at
 * @property \Illuminate\Support\Carbon|null $black_guard_active_at
 * @property \Illuminate\Support\Carbon|null $black_guard_inactive_at
 * @property \Illuminate\Support\Carbon|null $last_tick_at
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property \Illuminate\Support\Carbon|null $abandoned_at
 * @property int $protection_ticks_remaining
 * @property bool $ai_enabled
 * @property array|null $ai_config
 * @property int $monarchy_vote_for_dominion_id
 * @property array|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Council\Post[] $councilPosts
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Council\Thread[] $councilThreads
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion\History[] $history
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion\Journal[] $journals
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read \OpenDominion\Models\Pack|null $pack
 * @property-read \OpenDominion\Models\Race $race
 * @property-read \OpenDominion\Models\Realm $realm
 * @property-read \OpenDominion\Models\Round $round
 * @property-read \OpenDominion\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion active()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion query()
 * @mixin \Eloquent
 */
class Dominion extends AbstractModel
{
    use Notifiable;

    protected $casts = [
        'prestige' => 'integer',
        'peasants' => 'integer',
        'peasants_last_hour' => 'integer',
        'draft_rate' => 'integer',
        'morale' => 'integer',
        'spy_strength' => 'float',
        'wizard_strength' => 'float',
        'spy_mastery' => 'integer',
        'wizard_mastery' => 'integer',
        'resilience' => 'integer',
        'daily_platinum' => 'boolean',
        'daily_land' => 'boolean',
        'daily_actions' => 'integer',
        'resource_platinum' => 'integer',
        'resource_food' => 'integer',
        'resource_lumber' => 'integer',
        'resource_mana' => 'integer',
        'resource_ore' => 'integer',
        'resource_gems' => 'integer',
        'resource_tech' => 'integer',
        'resource_boats' => 'float',
        'improvement_science' => 'integer',
        'improvement_keep' => 'integer',
        'improvement_spires' => 'integer',
        'improvement_forges' => 'integer',
        'improvement_walls' => 'integer',
        'improvement_harbor' => 'integer',
        'military_draftees' => 'integer',
        'military_unit1' => 'integer',
        'military_unit2' => 'integer',
        'military_unit3' => 'integer',
        'military_unit4' => 'integer',
        'military_spies' => 'integer',
        'military_assassins' => 'integer',
        'military_wizards' => 'integer',
        'military_archmages' => 'integer',
        'land_plain' => 'integer',
        'land_mountain' => 'integer',
        'land_swamp' => 'integer',
        'land_cavern' => 'integer',
        'land_forest' => 'integer',
        'land_hill' => 'integer',
        'land_water' => 'integer',
        'building_home' => 'integer',
        'building_alchemy' => 'integer',
        'building_farm' => 'integer',
        'building_smithy' => 'integer',
        'building_masonry' => 'integer',
        'building_ore_mine' => 'integer',
        'building_gryphon_nest' => 'integer',
        'building_tower' => 'integer',
        'building_wizard_guild' => 'integer',
        'building_temple' => 'integer',
        'building_diamond_mine' => 'integer',
        'building_school' => 'integer',
        'building_lumberyard' => 'integer',
        'building_forest_haven' => 'integer',
        'building_factory' => 'integer',
        'building_guard_tower' => 'integer',
        'building_shrine' => 'integer',
        'building_barracks' => 'integer',
        'building_dock' => 'integer',
        'calculated_networth' => 'integer',
        'council_last_read' => 'datetime',
        'forum_last_read' => 'datetime',
        'town_crier_last_seen' => 'datetime',
        'wonders_last_seen' => 'datetime',
        'royal_guard_active_at' => 'datetime',
        'elite_guard_active_at' => 'datetime',
        'black_guard_active_at' => 'datetime',
        'black_guard_inactive_at' => 'datetime',
        'last_tick_at' => 'datetime',
        'hourly_activity' => 'string',
        'locked_at' => 'datetime',
        'abandoned_at' => 'datetime',
        'protection_ticks_remaining' => 'integer',
        'ai_enabled' => 'boolean',
        'ai_config' => 'array',
        'settings' => 'array',
    ];

    protected $dispatchesEvents = [
        'saved' => DominionSavedEvent::class,
    ];

    //protected $with = ['race', 'realm'];

    // Transient properties

    public $calc = null;

    // Relations

    public function councilThreads()
    {
        return $this->hasMany(Council\Thread::class);
    }

    public function councilPosts()
    {
        return $this->hasMany(Council\Post::class);
    }

    public function events()
    {
        return $this->sourceEvents()->union($this->targetEvents());
    }

    public function journals()
    {
        return $this->hasMany(Journal::class);
    }

    public function sourceEvents()
    {
        return $this->hasMany(GameEvent::class, 'source_id', 'id')->where('source_type', Dominion::class);
    }

    public function targetEvents()
    {
        return $this->hasMany(GameEvent::class, 'target_id', 'id')->where('target_type', Dominion::class);
    }

    public function infoOps()
    {
        return $this->hasMany(InfoOp::class, 'source_dominion_id', 'id');
    }

    public function history()
    {
        return $this->hasMany(Dominion\History::class);
    }

    public function hero()
    {
        return $this->hasOne(Hero::class);
    }

    public function heroes()
    {
        return $this->hasMany(Hero::class);
    }

    public function pack()
    {
        return $this->belongsTo(Pack::class);
    }

    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    public function rankings()
    {
        return $this->hasMany(DailyRanking::class);
    }

    public function realm()
    {
        return $this->belongsTo(Realm::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function spells()
    {
        return $this->belongsToMany(
            Spell::class,
            DominionSpell::class
        )
        ->withPivot('duration', 'cast_by_dominion_id')
        ->wherePivot('duration', '>', 0)
        ->withTimestamps();
    }

    public function techs()
    {
        return $this->belongsToMany(
            Tech::class,
            DominionTech::class
        )
        ->withTimestamps();
    }

    public function queues()
    {
        return $this->hasMany(Dominion\Queue::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tick()
    {
        return $this->hasOne(Dominion\Tick::class);
    }

    // Eloquent Query Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('round', function (Builder $query) {
            $query->where('start_date', '<=', now())
                ->where('end_date', '>', now());
        });
    }

    // Methods

    // todo: move to eloquent events, see $dispatchesEvents
    public function save(array $options = [])
    {
        $recordChanges = isset($options['event']);
        if ($recordChanges) {
            $dominionHistoryService = app(HistoryService::class);
            $deltaAttributes = $dominionHistoryService->getDeltaAttributes($this);
        }

        // Verify tick hasn't happened during this request
        if ($this->exists && !isset($options['protection']) && $this->last_tick_at != $this->fresh()->last_tick_at) {
            throw new GameException('The Emperor is currently collecting taxes and cannot fulfill your request. Please try again.');
        }

        $saved = parent::save($options);

        if ($saved && $recordChanges) {
            $extraAttributes = ['action', 'defense_reduced', 'queue', 'source_dominion_id', 'target_dominion_id', 'target_wonder_id'];
            foreach ($extraAttributes as $attr) {
                if (isset($options[$attr])) {
                    $deltaAttributes[$attr] = $options[$attr];
                }
            }
            /** @noinspection PhpUndefinedVariableInspection */
            $dominionHistoryService->record($this, $deltaAttributes, $options['event']);
        }

        return $saved;
    }

    public function getDirty()
    {
        $dirty = parent::getDirty();

        $query = $this->newModelQuery();

        $dominionHistoryService = app(HistoryService::class);
        $deltaAttributes = $dominionHistoryService->getDeltaAttributes($this);

        foreach ($deltaAttributes as $attr => $value) {
            if (gettype($this->getAttribute($attr)) != 'boolean') {
                $wrapped = $query->toBase()->grammar->wrap($attr);
                $dirty[$attr] = $query->toBase()->raw("$wrapped + $value");
            }
        }

        return $dirty;
    }

    /**
     * Route notifications for the mail channel.
     *
     * @return string
     */
    public function routeNotificationForMail(): string
    {
        // todo: test this
        return $this->user->email;
    }

    /**
     * Returns whether this Dominion instance is selected by the logged in user.
     *
     * @return bool
     */
    public function isSelectedByAuthUser()
    {
        // todo: move to SelectorService
        $dominionSelectorService = app(SelectorService::class);

        $selectedDominion = $dominionSelectorService->getUserSelectedDominion();

        if ($selectedDominion === null) {
            return false;
        }

        return ($this->id === $selectedDominion->id);
    }

    /**
     * Returns whether this Dominion is locked due to abandonment, the round having ended, or administrative action.
     *
     * Locked Dominions cannot perform actions and are read-only.
     *
     * @return bool
     */
    public function isLocked()
    {
        return ($this->isAbandoned() || $this->round->hasEnded() || $this->locked_at !== null);
    }

    /**
     * Returns whether this Dominion has been abandoned.
     *
     * @return bool
     */
    public function isAbandoned()
    {
        return ($this->abandoned_at !== null && $this->abandoned_at <= now());
    }

    /**
     * Marks the dominion as having requested abandonment.
     *
     * @return void
     */
    public function requestAbandonment()
    {
        $this->abandoned_at = now()->addHours(24)->startOfHour();
    }

    /**
     * Resets the abandonment request timer.
     *
     * @return void
     */
    public function resetAbandonment(int $hours = 24)
    {
        $resetTime = now()->addHours($hours)->startOfHour();
        if ($this->abandoned_at !== null && $this->abandoned_at < $resetTime) {
            $this->abandoned_at = $resetTime;
        }
    }

    /**
     * Marks the dominion as no longer having requested abandonment.
     *
     * @return void
     */
    public function cancelAbandonment()
    {
        $this->abandoned_at = null;
    }

    /**
     * Returns the choice for monarch of a Dominion.
     *
     * @return Dominion
     */
    public function monarchVote()
    {
        return $this->hasOne(static::class, 'id', 'monarchy_vote_for_dominion_id');
    }

    /**
     * Returns whether this Dominion is the monarch for its realm.
     *
     * @return bool
     */
    public function isMonarch()
    {
        $monarch = $this->realm->monarch;
        return ($monarch !== null && $this->id == $monarch->id);
    }

    /**
     * Returns whether this Dominion is the general for its realm.
     *
     * @return bool
     */
    public function isGeneral()
    {
        $general = $this->realm->general;
        return ($general !== null && $this->id == $general->id);
    }

    /**
     * Returns whether this Dominion is the spymaster for its realm.
     *
     * @return bool
     */
    public function isSpymaster()
    {
        $spymaster = $this->realm->spymaster;
        return ($spymaster !== null && $this->id == $spymaster->id);
    }

    /**
     * Returns whether this Dominion is the magister for its realm.
     *
     * @return bool
     */
    public function isMagister()
    {
        $magister = $this->realm->magister;
        return ($magister !== null && $this->id == $magister->id);
    }

    /**
     * Returns whether this Dominion is the mage for its realm.
     *
     * @return bool
     */
    public function isMage()
    {
        $mage = $this->realm->mage;
        return ($mage !== null && $this->id == $mage->id);
    }

    /**
     * Returns whether this Dominion is the jester for its realm.
     *
     * @return bool
     */
    public function isJester()
    {
        $jester = $this->realm->jester;
        return ($jester !== null && $this->id == $jester->id);
    }

    /**
     * Returns whether this Dominion holds any poisition in its realm.
     *
     * @return bool
     */
    public function isCourtMember()
    {
        return (
            $this->isMonarch() ||
            $this->isGeneral() ||
            $this->isSpymaster() ||
            $this->isMagister() ||
            $this->isMage() ||
            $this->isJester()
        );
    }

    /**
     * Returns the key for the court seat held by a Dominion.
     *
     * @return bool
     */
    public function getCourtSeat()
    {
        if ($this->isMonarch()) {
            return 'monarch';
        }
        if ($this->isGeneral()) {
            return 'general';
        }
        if ($this->isSpymaster()) {
            return 'spymaster';
        }
        if ($this->isMagister()) {
            return 'magister';
        }
        if ($this->isMage()) {
            return 'mage';
        }
        if ($this->isJester()) {
            return 'jester';
        }
    }

    /**
     * Return a boolean whether or not the dominion has protection ticks remaining.
     */
    public function isActive(): bool
    {
        return $this->protection_ticks_remaining == 0;
    }

    /**
     * Returns the amount of morale gained per hour.
     */
    public function getMoraleGain(): int
    {
        $moraleGain = 3;

        if ($this->morale < 80) {
            $moraleGain = 6;
        }

        return min($moraleGain, 100 - $this->morale);
    }

    /**
     * Returns the unit production bonus for a specific resource type (across all eligible units) for this dominion.
     *
     * @param string $resourceType
     * @return float
     */
    public function getUnitPerkProductionBonus(string $resourceType): float
    {
        $bonus = 0;

        foreach ($this->race->units as $unit) {
            $perkValue = $unit->getPerkValue($resourceType);

            if ($perkValue !== 0) {
                $bonus += ($this->{'military_unit' . $unit->slot} * (float)$perkValue);
            }
        }

        return $bonus;
    }

    public function getSpellPerks() {
        return $this->spells->flatMap(
            function ($spell) {
                return $spell->perks->map(
                    function ($perk) use ($spell) {
                        $perk->category = $spell->category;
                        return $perk;
                    }
                );
            }
        );
    }

    /**
     * @param string $key
     * @param array $types
     * @return float
     */
    public function getSpellPerkValue(string $key, array $types = ['self', 'friendly']): float
    {
        // TODO: Group by category and remove resolveSpellPerk
        $perks = $this->getSpellPerks()->whereIn('category', $types)->groupBy('key');
        if (isset($perks[$key])) {
            if ($perks[$key]->count() == 1) {
                return $perks[$key]->first()->pivot->value;
            }
            // Spell perks do not stack
            $perkValue = (float)$perks[$key]->max('pivot.value');
            if ($perkValue < 0) {
                $perkValue = (float)$perks[$key]->min('pivot.value');
            }
            return $perkValue;
        }
        return 0;
    }

    /**
     * @param string $key
     * @return float
     */
    public function getSpellPerkMultiplier(string $key): float
    {
        return ($this->getSpellPerkValue($key) / 100);
    }

    protected function getTechPerks() {
        return $this->techs->flatMap(
            function ($tech) {
                return $tech->perks;
            }
        );
    }

    /**
     * @param string $key
     * @return float
     */
    public function getTechPerkValue(string $key): float
    {
        $perks = $this->getTechPerks()->groupBy('key');
        if (isset($perks[$key])) {
            return (float)$perks[$key]->sum('pivot.value');
        }
        return 0;
    }

    /**
     * @param string $key
     * @return float
     */
    public function getTechPerkMultiplier(string $key): float
    {
        return ($this->getTechPerkValue($key) / 100);
    }

    protected function getWonderPerks() {
        if ($this->realm !== null) {
            return $this->realm->wonders->flatMap(
                function ($wonder) {
                    return $wonder->perks;
                }
            );
        } else {
            return collect([]);
        }
    }

    /**
     * @param string $key
     * @return float
     */
    public function getWonderPerkValue(string $key): float
    {
        $perks = $this->getWonderPerks()->groupBy('key');
        if (isset($perks[$key])) {
            $max = (float)$perks[$key]->max('pivot.value');
            if ($max < 0) {
                return (float)$perks[$key]->min('pivot.value');
            }
            return $max;
        }
        return 0;
    }

    /**
     * @param string $key
     * @return float
     */
    public function getWonderPerkMultiplier(string $key): float
    {
        return ($this->getWonderPerkValue($key) / 100);
    }

    public function getSetting(string $key)
    {
        if (!array_has($this->settings, $key)) {
            return null;
        }

        return array_get($this->settings, $key);
    }

    public function inRealmAndSharesAdvisors(Dominion $target): bool
    {
        if ($target == null) {
            return false;
        }

        if ($this->id == $target->id) {
            return true;
        }

        if ($this->realm_id !== $target->realm_id) {
            return false;
        }

        if ($this->locked_at !== null) {
            return false;
        }

        $dominionAdvisors = $target->getSetting('realmadvisors');

        // Realm Advisor is explicitly enabled
        if ($dominionAdvisors && array_key_exists($this->id, $dominionAdvisors) && $dominionAdvisors[$this->id] === true) {
            return true;
        }

        // Realm Advisor is explicity disabled
        if ($dominionAdvisors && array_key_exists($this->id, $dominionAdvisors) && $dominionAdvisors[$this->id] === false) {
            return false;
        }

        // Pack Advisor is enabled
        if ($target->user != null && $target->user->getSetting('packadvisors') !== false && ($this->pack_id != null && $this->pack_id == $target->pack_id)) {
            return true;
        }

        // Late starters disabled by default
        if ($this->created_at > $this->round->realmAssignmentDate()) {
            return false;
        }

        // Realm Advisor is enabled
        if ($target->user !== null && $target->user->getSetting('realmadvisors') !== false) {
            return true;
        }

        return false;
    }

    public function sharesUsername(Dominion $target): bool
    {
        if ($target->user_id == null) {
            return false;
        }

        if ($this->id == $target->id) {
            return true;
        }

        // Always shared with packmates
        if ($target->pack_id !== null && $target->pack_id == $this->pack_id) {
            return true;
        }

        // Shared display name is enabled
        if ($target->user->getSetting('shareusername') !== false) {
            return true;
        }

        return false;
    }
}

<?php

namespace OpenDominion\Models;

use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Services\NotificationService;

/**
 * OpenDominion\Models\Hero
 *
 * @property int $id
 * @property int $dominion_id
 * @property string $name
 * @property string $class
 * @property int $experience
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\HeroBattle[] $battles
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Hero newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Hero newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Hero query()
 * @mixin \Eloquent
 */
class Hero extends AbstractModel
{
    protected $dates = ['created_at', 'updated_at'];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }

    public function battles()
    {
        return $this->belongsToMany(HeroBattle::class, HeroCombatant::class);
    }

    public function combatants()
    {
        return $this->hasMany(HeroCombatant::class);
    }

    public function queue()
    {
        return $this->hasMany(HeroBattleQueue::class);
    }

    public function combatActionRequired(): int
    {
        $ongoingBattles = $this->combatants()
            ->join('hero_battles', 'hero_battles.id', '=', 'hero_combatants.hero_battle_id')
            ->where('hero_battles.finished', false)
            ->where(function ($query) {
                $query->where('automated', null)
                    ->orWhere(function ($query) {
                        $query->where('automated', false)
                            ->where(function ($query) {
                                $query->whereNull('actions')
                                ->orWhere('actions', '[]');
                            });
                    });
            });

        return $ongoingBattles->count();
    }

    public function isInQueue(): bool
    {
        return $this->queue()->exists();
    }

    public function upgrades()
    {
        return $this->belongsToMany(
            HeroUpgrade::class,
            HeroHeroUpgrade::class
        )
        ->withTimestamps();
    }

    public function getPerks() {
        return $this->upgrades->flatMap(
            function ($upgrade) {
                return $upgrade->perks;
            }
        );
    }

    /**
     * @param string $key
     * @return float
     */
    public function getPerkValue(string $key): float
    {
        $perks = $this->getPerks()->groupBy('key');
        if (isset($perks[$key])) {
            return (float)$perks[$key]->sum('value');
        }
        return 0;
    }

    /**
     * @param string $key
     * @return float
     */
    public function getPerkMultiplier(string $key): float
    {
        return ($this->getPerkValue($key) / 100);
    }

    public function save(array $options = [])
    {
        $original = $this->getOriginal();

        if ($original && isset($original['experience'])) {
            $heroCalculator = app(HeroCalculator::class);

            $previousLevel = $heroCalculator->getExperienceLevel($original['experience']);
            $currentLevel = $heroCalculator->getHeroLevel($this);
            if ($previousLevel != $currentLevel) {
                $notificationService = app(NotificationService::class);
                $notificationService->queueNotification('hero_level', [
                    'level' => $currentLevel,
                ])->sendNotifications($this->dominion, 'irregular_dominion');
            }
        }

        $saved = parent::save($options);

        return $saved;
    }
}

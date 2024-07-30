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

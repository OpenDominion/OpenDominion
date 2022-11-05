<?php

namespace OpenDominion\Models;

use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Services\NotificationService;

/**
 * OpenDominion\Models\Hero
 *
 * @property int $id
 * @property string $name
 * @property string $class
 * @property string $vocation
 * @property int $experience
 * @property int $level
 * @property \Illuminate\Support\Carbon|null $returning_at
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Hero newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Hero newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Hero query()
 * @mixin \Eloquent
 */
class Hero extends AbstractModel
{
    protected $dates = ['returning_at', 'created_at', 'updated_at'];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }

    public function save(array $options = [])
    {
        $heroCalculator = app(HeroCalculator::class);

        $previousLevel = $heroCalculator->getExperienceLevel($this->getOriginal()['experience']);
        $currentLevel = $heroCalculator->getHeroLevel($this);
        if ($previousLevel != $currentLevel) {
            $notificationService = app(NotificationService::class);
            $notificationService->queueNotification('hero_level', [
                'level' => $currentLevel,
            ]);
            $notificationService->sendNotifications($this->dominion, 'irregular_dominion');
        }

        $saved = parent::save($options);

        return $saved;
    }
}

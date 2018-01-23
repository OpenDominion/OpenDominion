<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;
use OpenDominion\Services\Dominion\SelectorService;

class Dominion extends AbstractModel
{
    use Notifiable;

    // Relations

    public function councilThreads()
    {
        return $this->hasMany(Council\Thread::class);
    }

    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    public function realm()
    {
        return $this->belongsTo(Realm::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    public function scopeActive(Builder $query)
    {
        return $query->whereHas('round', function (Builder $query) {
            $query->where('start_date', '<=', now())
                ->where('end_date', '>', now());
        });
    }

    // Methods

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
        // todo: repository criteria?
        $dominionSelectorService = app(SelectorService::class);

        $selectedDominion = $dominionSelectorService->getUserSelectedDominion();

        if ($selectedDominion === null) {
            return false;
        }

        return ($this->id === $selectedDominion->id);
    }

    /**
     * Returns whether this Dominion is locked due to the round having ended.
     *
     * Locked Dominions cannot perform actions and are read-only.
     *
     * @return bool
     */
    public function isLocked()
    {
        return (now() >= $this->round->end_date);
    }
}

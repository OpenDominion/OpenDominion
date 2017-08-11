<?php

namespace OpenDominion\Models;

use Carbon\Carbon;
use OpenDominion\Contracts\Services\Dominion\SelectorService;

class Dominion extends AbstractModel
{
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
        return (Carbon::now() >= $this->round->end_date);
    }
}

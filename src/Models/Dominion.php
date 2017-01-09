<?php

namespace OpenDominion\Models;

use OpenDominion\Services\DominionSelectorService;

class Dominion extends AbstractModel
{
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

    public function selectedByAuthUser()
    {
        // todo: repository criteria?
        $dominionSelectorService = app()->make(DominionSelectorService::class);

        $selectedDominion = $dominionSelectorService->getUserSelectedDominion();

        if ($selectedDominion === null) {
            return false;
        }

        return ($this->id === $selectedDominion->id);
    }
}

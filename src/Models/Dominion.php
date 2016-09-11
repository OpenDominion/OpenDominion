<?php

namespace OpenDominion\Models;

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

    public function updateNetworth()
    {
        $networth = 0;

        $units = $this->race->units;
        foreach ($units as $unit) {
            $networth += ($unit->getNetworth() * $this->{'military_unit' . $unit->slot});
        }
        $networth += (5 * $this->military_spies);
        $networth += (5 * $this->military_wizards);
        $networth += (5 * $this->military_archmages);

        // todo: land
        // todo: buildings

        $this->networth = $networth;
        $this->save();
    }
}

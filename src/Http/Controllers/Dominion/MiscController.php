<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Models\Pack;

// misc functions, probably could use a refactor later
class MiscController extends AbstractDominionController
{
    public function postClearNotifications()
    {
        $this->getSelectedDominion()->notifications()->delete();
        return redirect()->back();
    }

    public function postClosePack()
    {
        /** @var Pack $pack */
        $pack = $this->getSelectedDominion()->pack;
        $pack->closed_at = now();
        $pack->save();
        return redirect()->back();
    }
}

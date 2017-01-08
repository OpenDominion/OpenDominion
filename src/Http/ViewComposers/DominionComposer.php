<?php

namespace OpenDominion\Http\ViewComposers;

use Illuminate\View\View;
use OpenDominion\Repositories\DominionRepository;

class DominionComposer
{
    /** @var DominionRepository */
    protected $dominions;

    protected $dominion;

    public function __construct(DominionRepository $dominions)
    {
        $this->dominions = $dominions;
    }

    public function compose(View $view)
    {
        $dominionId = session('dominion_id');

        if (!$dominionId) {
            return;
        }

        if (!$this->dominion || ($this->dominion->id !== $dominionId)) {
            $this->dominion = $this->dominions->find($dominionId);
        }

        $view->with('selectedDominion', $this->dominion);
    }
}

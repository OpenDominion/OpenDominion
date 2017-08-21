<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Contracts\Services\Dominion\Actions\ReleaseActionService as ReleaseActionServiceContract;
use OpenDominion\Models\Dominion;
use RuntimeException;

class ReleaseActionService implements ReleaseActionServiceContract
{
    /**
     * {@inheritdoc}
     */
    public function release(Dominion $dominion, array $data)
    {
        dd($data);
    }
}

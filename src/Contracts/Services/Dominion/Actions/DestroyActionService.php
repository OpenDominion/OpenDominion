<?php

namespace OpenDominion\Contracts\Services\Dominion\Actions;

use OpenDominion\Models\Dominion;
use RuntimeException;

interface DestroyActionService
{
    /**
     * Does a destroy buildings action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws RuntimeException
     */
    public function destroy(Dominion $dominion, array $data);
}

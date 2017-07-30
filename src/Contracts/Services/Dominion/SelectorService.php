<?php

namespace OpenDominion\Contracts\Services\Dominion;

use Exception;
use OpenDominion\Models\Dominion;

interface SelectorService
{
    /**
     * @return bool
     */
    public function hasUserSelectedDominion();

    /**
     * @param Dominion $dominion
     * @throws Exception
     */
    public function selectUserDominion(Dominion $dominion);

    /**
     * @return Dominion|null
     */
    public function getUserSelectedDominion();

    /**
     * @return void
     */
    public function unsetUserSelectedDominion();
}

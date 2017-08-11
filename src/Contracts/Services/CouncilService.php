<?php

namespace OpenDominion\Contracts\Services;

use OpenDominion\Models\Council;
use OpenDominion\Models\Dominion;
use RuntimeException;

interface CouncilService
{
    /**
     * Creates a new council thread.
     *
     * @param Dominion $dominion
     * @param string $title
     * @param string $body
     * @return Council\Thread
     * @throws RuntimeException
     */
    public function createThread(Dominion $dominion, string $title, string $body);

    /**
     * Creates a reply post on a council thread.
     *
     * @param Dominion $dominion
     * @param Council\Thread $thread
     * @param string $body
     * @return Council\Post
     * @throws RuntimeException
     */
    public function postReply(Dominion $dominion, Council\Thread $thread, string $body);
}

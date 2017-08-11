<?php

namespace OpenDominion\Services;

use OpenDominion\Contracts\Services\CouncilService as CouncilServiceContract;
use OpenDominion\Models\Council;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class CouncilService implements CouncilServiceContract
{
    use DominionGuardsTrait;

    /**
     * {@inheritdoc}
     */
    public function createThread(Dominion $dominion, string $title, string $body)
    {
        $this->guardLockedDominion($dominion);

        return Council\Thread::create([
            'realm_id' => $dominion->realm->id,
            'dominion_id' => $dominion->id,
            'title' => $title,
            'body' => $body,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function postReply(Dominion $dominion, Council\Thread $thread, string $body)
    {
        $this->guardLockedDominion($dominion);

        if ($dominion->realm->id !== (int)$thread->realm_id) {
            throw new RuntimeException("Dominion {$dominion->name} in realm {$dominion->realm->id} may not post to thread in realm {$thread->realm_id}.");
        }

        return Council\Post::create([
            'council_thread_id' => $thread->id,
            'dominion_id' => $dominion->id,
            'body' => $body,
        ]);
    }
}

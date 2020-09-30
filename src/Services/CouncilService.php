<?php

namespace OpenDominion\Services;

use DB;
use Illuminate\Database\Eloquent\Collection;
use OpenDominion\Models\Council;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class CouncilService
{
    use DominionGuardsTrait;

    /**
     * Returns the realm's
     *
     * @param Realm $realm
     * @return LengthAwarePaginator
     */
    public function getThreads(Realm $realm)
    {
        $resultsPerPage = 15;

        return $realm->councilThreads()
            ->select([
                'council_threads.*',
                DB::raw('IFNULL(MAX(council_posts.created_at), council_threads.created_at) as last_activity')
            ])
            ->with(['dominion.realm', 'posts.dominion.realm'])
            ->leftJoin('council_posts', 'council_posts.council_thread_id', '=', 'council_threads.id')
            ->groupBy('council_threads.id')
            ->orderBy('last_activity', 'desc')
            ->paginate($resultsPerPage);
    }

    /**
     * Creates a new council thread.
     *
     * @param Dominion $dominion
     * @param string $title
     * @param string $body
     * @return Council\Thread
     * @throws RuntimeException
     */
    public function createThread(Dominion $dominion, string $title, string $body): Council\Thread
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
     * Creates a reply post on a council thread.
     *
     * @param Dominion $dominion
     * @param Council\Thread $thread
     * @param string $body
     * @return Council\Post
     * @throws RuntimeException
     */
    public function postReply(Dominion $dominion, Council\Thread $thread, string $body): Council\Post
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

    /**
     * Deletes a council thread.
     *
     * @param Dominion $dominion
     * @param Council\Thread $thread
     * @return void
     * @throws RuntimeException
     */
    public function deleteThread(Dominion $dominion, Council\Thread $thread): void
    {
        $this->guardLockedDominion($dominion);
        $thread->delete();
    }

    /**
     * Deletes a council post.
     *
     * @param Dominion $dominion
     * @param Council\Post $post
     * @return void
     * @throws RuntimeException
     */
    public function deletePost(Dominion $dominion, Council\Post $post): void
    {
        $this->guardLockedDominion($dominion);
        $post->delete();
    }
}

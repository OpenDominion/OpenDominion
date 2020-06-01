<?php

namespace OpenDominion\Services;

use DB;
use Illuminate\Database\Eloquent\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Forum;
use OpenDominion\Models\Round;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class ForumService
{
    use DominionGuardsTrait;

    /**
     * Returns the round's forum threads.
     *
     * @param Round $round
     * @return Collection|Forum\Thread[]
     */
    public function getThreads(Round $round): Collection
    {
        return $round->forumThreads()
            ->select([
                'forum_threads.*',
                DB::raw('IFNULL(MAX(forum_posts.created_at), forum_threads.created_at) as last_activity')
            ])
            ->with(['dominion.realm', 'posts.dominion.realm'])
            ->leftJoin('forum_posts', 'forum_posts.forum_thread_id', '=', 'forum_threads.id')
            ->groupBy('forum_threads.id')
            ->orderBy('last_activity', 'desc')
            ->get(['forum_threads.*'])
            ->filter(function ($thread) {
                if ($thread->flagged_for_removal && $thread->unflaggedPosts->isEmpty()) {
                    return false;
                }
                return true;
            });
    }

    /**
     * Creates a new forum thread.
     *
     * @param Dominion $dominion
     * @param string $title
     * @param string $body
     * @return Forum\Thread
     * @throws RuntimeException
     */
    public function createThread(Dominion $dominion, string $title, string $body): Forum\Thread
    {
        $this->guardLockedDominion($dominion);

        return Forum\Thread::create([
            'round_id' => $dominion->round->id,
            'dominion_id' => $dominion->id,
            'title' => $title,
            'body' => $body,
        ]);
    }

    /**
     * Creates a reply post on a forum thread.
     *
     * @param Dominion $dominion
     * @param Forum\Thread $thread
     * @param string $body
     * @return Forum\Post
     * @throws RuntimeException
     */
    public function postReply(Dominion $dominion, Forum\Thread $thread, string $body): Forum\Post
    {
        $this->guardLockedDominion($dominion);

        return Forum\Post::create([
            'forum_thread_id' => $thread->id,
            'dominion_id' => $dominion->id,
            'body' => $body,
        ]);
    }

    /**
     * Deletes a forum thread.
     *
     * @param Dominion $dominion
     * @param Forum\Thread $thread
     * @return void
     * @throws RuntimeException
     */
    public function deleteThread(Dominion $dominion, Forum\Thread $thread): void
    {
        $this->guardLockedDominion($dominion);

        // create a post or delete thread completely
        if ($thread->posts->isEmpty()) {
            $thread->delete();
        } else {
            // Save existing thread body as soft-deleted post
            Forum\Post::create([
                'forum_thread_id' => $thread->id,
                'dominion_id' => $dominion->id,
                'body' => $thread->body,
                'created_at' => $thread->created_at,
                'deleted_at' => now(),
            ]);

            $thread->body = '_This post has been deleted._';
            $thread->save();
        }
    }

    /**
     * Deletes a forum post.
     *
     * @param Dominion $dominion
     * @param Forum\Post $post
     * @return void
     * @throws RuntimeException
     */
    public function deletePost(Dominion $dominion, Forum\Post $post): void
    {
        $this->guardLockedDominion($dominion);
        $post->delete();
    }

    /**
     * Flags a forum thread for removal.
     *
     * @param Dominion $dominion
     * @param Forum\Thread $thread
     * @return void
     * @throws RuntimeException
     */
    public function flagThread(Dominion $dominion, Forum\Thread $thread): void
    {
        $this->guardLockedDominion($dominion);

        if (!$thread->flagged_by || !isset($thread->flagged_by['dominion_ids']) || !isset($thread->flagged_by['realm_ids'])) {
            $dominion_ids = [$dominion->id];
            $realm_ids = [$dominion->realm_id];
        } else {
            $dominion_ids = $thread->flagged_by['dominion_ids'];
            $dominion_ids[] = $dominion->id;
            $dominion_ids = array_unique($dominion_ids);
            $realm_ids = $thread->flagged_by['realm_ids'];
            $realm_ids[] = $dominion->realm_id;
            $realm_ids = array_unique($realm_ids);
        }

        $thread->flagged_by = [
            'dominion_ids' => $dominion_ids,
            'realm_ids' => $realm_ids,
        ];

        // Remove thread if it has been flagged by
        // 5 different dominions from at least 3 different realms
        if (count($dominion_ids) >= 5 && count($realm_ids) >= 3) {
            $thread->flagged_for_removal = true;
        }

        $thread->save();
    }

    /**
     * Flags a forum post for removal.
     *
     * @param Dominion $dominion
     * @param Forum\Post $post
     * @return void
     * @throws RuntimeException
     */
    public function flagPost(Dominion $dominion, Forum\Post $post): void
    {
        $this->guardLockedDominion($dominion);

        if (!$post->thread->flagged_by || !isset($post->thread->flagged_by['dominion_ids']) || !isset($post->thread->flagged_by['realm_ids'])) {
            $dominion_ids = [$dominion->id];
            $realm_ids = [$dominion->realm_id];
        } else {
            $dominion_ids = $post->thread->flagged_by['dominion_ids'];
            $dominion_ids[] = $dominion->id;
            $dominion_ids = array_unique($dominion_ids);
            $realm_ids = $post->thread->flagged_by['realm_ids'];
            $realm_ids[] = $dominion->realm_id;
            $realm_ids = array_unique($realm_ids);
        }

        $post->thread->flagged_by = [
            'dominion_ids' => $dominion_ids,
            'realm_ids' => $realm_ids,
        ];

        // Remove post if it has been flagged by
        // 5 different dominions from at least 3 different realms
        if (count($dominion_ids) >= 5 && count($realm_ids) >= 3) {
            $post->thread->flagged_for_removal = true;
        }

        $post->thread->save();
    }
}

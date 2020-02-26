<?php

namespace OpenDominion\Services;

use DB;
use Illuminate\Database\Eloquent\Collection;
use OpenDominion\Models\Forum;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class ForumService
{
    use DominionGuardsTrait;

    /**
     * Returns the round's
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
            ->with(['dominion.user', 'posts.dominion.user'])
            ->leftJoin('forum_posts', 'forum_posts.forum_thread_id', '=', 'forum_threads.id')
            ->groupBy('forum_threads.id')
            ->orderBy('last_activity', 'desc')
            ->get(['forum_threads.*']);
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

            $thread->body = "_This post has been deleted._";
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
}

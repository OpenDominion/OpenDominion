<?php

namespace OpenDominion\Services;

use DB;
use Illuminate\Database\Eloquent\Collection;
use OpenDominion\Models\MessageBoard;
use OpenDominion\Models\User;
use RuntimeException;

class MessageBoardService
{
    /**
     * Returns message board threads.
     *
     * @return LengthAwarePaginator
     */
    public function getThreads()
    {
        $resultsPerPage = 15;

        return MessageBoard\Thread::query()
            ->select([
                'message_board_threads.*',
                DB::raw('IFNULL(MAX(message_board_posts.created_at), message_board_threads.created_at) as last_activity')
            ])
            ->with(['user'])
            ->leftJoin('message_board_posts', 'message_board_posts.message_board_thread_id', '=', 'message_board_threads.id')
            ->groupBy('message_board_threads.id')
            ->orderBy('last_activity', 'desc')
            ->paginate($resultsPerPage);
            /*
            ->filter(function ($thread) {
                if ($thread->flagged_for_removal && $thread->unflaggedPosts->isEmpty()) {
                    return false;
                }
                return true;
            });
            */
    }

    /**
     * Creates a new message board thread.
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @return MessageBoard\Thread
     * @throws RuntimeException
     */
    public function createThread(User $user, string $title, string $body): MessageBoard\Thread
    {
        return MessageBoard\Thread::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
        ]);
    }

    /**
     * Creates a reply post on a message board thread.
     *
     * @param User $user
     * @param MessageBoard\Thread $thread
     * @param string $body
     * @return MessageBoard\Post
     * @throws RuntimeException
     */
    public function postReply(User $user, MessageBoard\Thread $thread, string $body): MessageBoard\Post
    {
        return MessageBoard\Post::create([
            'message_board_thread_id' => $thread->id,
            'user_id' => $user->id,
            'body' => $body,
        ]);
    }

    /**
     * Deletes a message board thread.
     *
     * @param User $user
     * @param MessageBoard\Thread $thread
     * @return void
     * @throws RuntimeException
     */
    public function deleteThread(User $user, MessageBoard\Thread $thread): void
    {
        // create a post or delete thread completely
        if ($thread->posts->isEmpty()) {
            $thread->delete();
        } else {
            // Save existing thread body as soft-deleted post
            MessageBoard\Post::create([
                'message_board_thread_id' => $thread->id,
                'user_id' => $user->id,
                'body' => $thread->body,
                'created_at' => $thread->created_at,
                'deleted_at' => now(),
            ]);

            $thread->body = '_This post has been deleted._';
            $thread->save();
        }
    }

    /**
     * Deletes a message board post.
     *
     * @param User $user
     * @param MessageBoard\Post $post
     * @return void
     * @throws RuntimeException
     */
    public function deletePost(User $user, MessageBoard\Post $post): void
    {
        $post->delete();
    }

    /**
     * Flags a message board thread for removal.
     *
     * @param User $user
     * @param MessageBoard\Thread $thread
     * @return void
     * @throws RuntimeException
     */
    public function flagThread(User $user, MessageBoard\Thread $thread): void
    {
        if (!$thread->flagged_by || !isset($thread->flagged_by['user_ids'])) {
            $user_ids = [$user->id];
        } else {
            $user_ids = $thread->flagged_by['user_ids'];
            $user_ids[] = $user->id;
            $user_ids = array_unique($user_ids);
        }

        $thread->flagged_by = [
            'user_ids' => $user_ids
        ];

        // Remove thread if it has been flagged by 5 different users
        if (count($dominion_ids) >= 5) {
            $thread->flagged_for_removal = true;
        }

        $thread->save();
    }

    /**
     * Flags a message board post for removal.
     *
     * @param User $user
     * @param MessageBoard\Post $post
     * @return void
     * @throws RuntimeException
     */
    public function flagPost(User $user, MessageBoard\Post $post): void
    {
        if (!$post->thread->flagged_by || !isset($post->thread->flagged_by['user_ids'])) {
            $user_ids = [$user->id];
        } else {
            $user_ids = $post->thread->flagged_by['user_ids'];
            $user_ids[] = $user->id;
            $user_ids = array_unique($user_ids);
        }

        $post->thread->flagged_by = [
            'user_ids' => $user_ids
        ];

        // Remove post if it has been flagged by 5 different users
        if (count($user_ids) >= 5) {
            $post->thread->flagged_for_removal = true;
        }

        $post->thread->save();
    }
}

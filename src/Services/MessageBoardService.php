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
     * @return Collection
     */
    public function getCategories(): Collection
    {
        return MessageBoard\Category::all();
    }

    /**
     * Returns message board threads.
     *
     * @param MessageBoard\Category $category
     * @return LengthAwarePaginator
     */
    public function getThreads(MessageBoard\Category $category)
    {
        $resultsPerPage = 15;

        return $category->threads()
            ->with(['user', 'posts', 'latestPost.user'])
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
     * @param array{
     *     homepage_display?: bool,
     *     homepage_preset?: string|null,
     *     homepage_subtitle?: string|null,
     *     homepage_url?: string|null
     * } $homepageFields
     */
    public function createThread(User $user, MessageBoard\Category $category, string $title, string $body, array $homepageFields = []): MessageBoard\Thread
    {
        $attributes = [
            'user_id' => $user->id,
            'message_board_category_id' => $category->id,
            'title' => $title,
            'body' => $body,
            'last_activity' => now(),
        ];

        $attributes = array_merge($attributes, $this->resolveHomepageAttributes($category, $homepageFields));

        return MessageBoard\Thread::create($attributes);
    }

    /**
     * Updates a thread's title, body, and (for Announcements threads) its homepage settings.
     * Caller is responsible for authorization.
     *
     * @param array{
     *     title?: string,
     *     body?: string,
     *     homepage_display?: bool,
     *     homepage_preset?: string|null,
     *     homepage_subtitle?: string|null,
     *     homepage_url?: string|null
     * } $fields
     */
    public function editThread(MessageBoard\Thread $thread, array $fields): MessageBoard\Thread
    {
        if (array_key_exists('title', $fields)) {
            $thread->title = $fields['title'];
        }
        if (array_key_exists('body', $fields)) {
            $thread->body = $fields['body'];
        }

        $homepage = $this->resolveHomepageAttributes($thread->category, $fields);
        foreach ($homepage as $column => $value) {
            $thread->{$column} = $value;
        }

        $thread->save();

        return $thread;
    }

    /**
     * Returns the homepage_* attributes that should be persisted for this thread.
     * Returns an empty array if the category is not Announcements.
     * Callers are responsible for verifying the user is authorized to set these fields.
     *
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    protected function resolveHomepageAttributes(MessageBoard\Category $category, array $fields): array
    {
        if ($category->slug !== 'announcements') {
            return [];
        }

        $display = (bool) ($fields['homepage_display'] ?? false);
        $preset = $fields['homepage_preset'] ?? null;
        if ($display && empty($preset)) {
            $preset = 'announcement';
        }

        return [
            'homepage_display' => $display,
            'homepage_preset' => $preset,
            'homepage_subtitle' => $fields['homepage_subtitle'] ?? null,
            'homepage_url' => $fields['homepage_url'] ?? null,
        ];
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
        if (count($user_ids) >= 5) {
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
        if (!$post->flagged_by || !isset($post->flagged_by['user_ids'])) {
            $user_ids = [$user->id];
        } else {
            $user_ids = $post->flagged_by['user_ids'];
            $user_ids[] = $user->id;
            $user_ids = array_unique($user_ids);
        }

        $post->flagged_by = [
            'user_ids' => $user_ids
        ];

        // Remove post if it has been flagged by 5 different users
        if (count($user_ids) >= 5) {
            $post->flagged_for_removal = true;
        }

        $post->save();
    }
}

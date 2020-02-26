<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Forum\CreatePostRequest;
use OpenDominion\Http\Requests\Dominion\Forum\CreateThreadRequest;
use OpenDominion\Models\Forum;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Services\ForumService;

class ForumController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();
        //$this->updateDominionForumLastRead($dominion);
        $forumService = app(ForumService::class);

        $threads = $forumService->getThreads($dominion->round);

        return view('pages.dominion.forum.index', [
            'forumThreads' => $threads,
            'round' => $dominion->round,
        ]);
    }

    public function getCreate() // getCreateThread?
    {
        $dominion = $this->getSelectedDominion();
        $round = $dominion->round;

        return view('pages.dominion.forum.create', compact(
            'round'
        ));
    }

    public function postCreate(CreateThreadRequest $request) // postCreateThread
    {
        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        try {
            $thread = $forumService->createThread(
                $dominion,
                $request->get('title'),
                $request->get('body')
            );

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: fire laravel event
//        $analyticsService = app(AnalyticsService::class);
//        $analyticsService->queueFlashEvent(new Event( // todo: contract
//            'forum',
//            'create-thread',
//            $thread->title, // ?
//            null
//        ));

        $request->session()->flash('alert-success', 'Your thread has been created');
        return redirect()->route('dominion.forum.thread', $thread);
    }

    public function getThread(Forum\Thread $thread)
    {
        try {
            $this->guardAgainstCrossRound($thread);
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $dominion = $this->getSelectedDominion();
        //$this->updateDominionForumLastRead($dominion);

        $thread->load(['dominion.user', 'posts.dominion.user']);

        return view('pages.dominion.forum.thread', compact(
            'thread'
        ));
    }

    public function postReply(CreatePostRequest $request, Forum\Thread $thread)
    {
        try {
            $this->guardAgainstCrossRound($thread);
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        try {
            // todo: $post = ... and navigate to anchor with post id on page?
            $forumService->postReply($dominion, $thread, $request->get('body'));

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: fire laravel event
//        $analyticsService = app(AnalyticsService::class);
//        $analyticsService->queueFlashEvent(new Event( // todo: contract
//            'dominion.forum',
//            'create-post',
//            $thread->title, // ?
//            null
//        ));

        $request->session()->flash('alert-success', 'Your message has been posted');
        return redirect()->route('dominion.forum.thread', $thread);
    }

    public function getDeletePost(Forum\Post $post)
    {
        try {
            $this->guardForPost($post);
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $post->load(['dominion.user']);

        return view('pages.dominion.forum.delete-post', compact(
            'post'
        ));
    }

    public function postDeletePost(Request $request, Forum\Post $post)
    {
        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        try {
            $this->guardForPost($post);
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $forumService->deletePost($dominion, $post);

        $request->session()->flash('alert-success', 'Post successfully deleted.');
        return redirect()->route('dominion.forum.thread', $post->thread);
    }

    public function getDeleteThread(Forum\Thread $thread)
    {
        try {
            $this->guardForThread($thread);
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $thread->load(['dominion.user', 'posts.dominion.user']);

        return view('pages.dominion.forum.delete-thread', compact(
            'thread'
        ));
    }

    public function postDeleteThread(Request $request, Forum\Thread $thread)
    {
        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        try {
            $this->guardForThread($thread);
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $forumService->deleteThread($dominion, $thread);

        $request->session()->flash('alert-success', 'Thread successfully deleted.');
        return redirect()->route('dominion.forum');
    }

    /**
     * Throws exception if trying to view a thread outside of the round.
     *
     * @param Forum\Thread $thread
     * @throws GameException
     */
    protected function guardAgainstCrossRound(Forum\Thread $thread): void
    {
        if ($this->getSelectedDominion()->round_id !== (int)$thread->round_id) {
            throw new GameException('No permission to view thread.');
        }
    }

    /**
     * Throws exception if the selected dominion is not the thread's creator.
     *
     * @param Thread $thread
     * @throws GameException
     */
    protected function guardForThread(Forum\Thread $thread): void
    {
        if ($this->getSelectedDominion()->id !== (int)$thread->dominion_id) {
            throw new GameException('No permission to moderate thread.');
        }
    }

    /**
     * Throws exception if the selected dominion is not the post's creator.
     *
     * @param Post $post
     * @throws GameException
     */
    protected function guardForPost(Forum\Post $post): void
    {
        if ($this->getSelectedDominion()->id !== (int)$post->dominion_id) {
            throw new GameException('No permission to moderate post.');
        }
    }

    /*
    protected function updateDominionForumLastRead(Dominion $dominion): void
    {
        $dominion->forum_last_read = now();
        $dominion->save();
    }
    */
}

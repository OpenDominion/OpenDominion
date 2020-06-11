<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Forum\CreatePostRequest;
use OpenDominion\Http\Requests\Dominion\Forum\CreateThreadRequest;
use OpenDominion\Helpers\RankingsHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Forum;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\RankingsService;
use OpenDominion\Services\ForumService;

class ForumController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();
        $this->updateDominionForumLastRead($dominion);
        $forumService = app(ForumService::class);
        $protectionService = app(ProtectionService::class);

        $announcements = $dominion->round->forumAnnouncements()->orderBy('created_at', 'desc')->get();
        $threads = $forumService->getThreads($dominion->round);

        return view('pages.dominion.forum.index', [
            'announcements' => $announcements,
            'forumThreads' => $threads,
            'round' => $dominion->round,
            'protectionService' => $protectionService,
        ]);
    }

    public function getAnnouncement(Forum\Announcement $announcement)
    {
        return view('pages.dominion.forum.announcement', compact(
            'announcement',
        ));
    }

    public function getCreate() // getCreateThread?
    {
        $dominion = $this->getSelectedDominion();
        $round = $dominion->round;

        try {
            $this->guardAgainstProtection();
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.dominion.forum.create', compact(
            'round'
        ));
    }

    public function postCreate(CreateThreadRequest $request) // postCreateThread
    {
        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        try {
            $this->guardAgainstRepeatOffenders();
            $this->guardAgainstProtection();
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
            $this->guardAgainstProtection();
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $dominion = $this->getSelectedDominion();
        $this->updateDominionForumLastRead($dominion);

        $thread->load('dominion.realm', 'posts.dominion.realm');
        $rankingsHelper = app(RankingsHelper::class);
        $rankingsService = app(RankingsService::class);

        return view('pages.dominion.forum.thread', compact(
            'thread',
            'rankingsHelper',
            'rankingsService'
        ));
    }

    public function postReply(CreatePostRequest $request, Forum\Thread $thread)
    {
        try {
            $this->guardAgainstCrossRound($thread);
            $this->guardAgainstRepeatOffenders();
            $this->guardAgainstProtection();
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
            $this->guardAgainstProtection();
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

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
            $this->guardAgainstProtection();
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
            $this->guardAgainstProtection();
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $thread->load('dominion.realm', 'posts.dominion.realm');

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
            $this->guardAgainstProtection();
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $forumService->deleteThread($dominion, $thread);

        $request->session()->flash('alert-success', 'Thread successfully deleted.');
        return redirect()->route('dominion.forum');
    }

    public function getFlagPost(Request $request, Forum\Post $post)
    {
        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        $forumService->flagPost($dominion, $post);

        $request->session()->flash('alert-success', 'Post successfully flagged for removal.');
        return redirect()->route('dominion.forum.thread', $post->thread);
    }

    public function getFlagThread(Request $request, Forum\Thread $thread)
    {
        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        $forumService->flagThread($dominion, $thread);

        $request->session()->flash('alert-success', 'Thread successfully flagged for removal.');
        return redirect()->route('dominion.forum.thread', $thread);
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

    /**
     * Throws exception if the selected dominion has abused posting privileges
     *
     * @throws GameException
     */
    protected function guardAgainstRepeatOffenders(): void
    {
        $flaggedThreadCount = Forum\Post::where('flagged_for_removal', true)->where('dominion_id', $this->getSelectedDominion()->id)->count();
        $flaggedPostCount = Forum\Post::where('flagged_for_removal', true)->where('dominion_id', $this->getSelectedDominion()->id)->count();
        if (($flaggedThreadCount + $flaggedPostCount) >= 5) {
            throw new GameException('You have been banned from posting for the remainder of the round.');
        }
    }

    /**
     * Throws exception if the selected dominion is still under protection
     *
     * @throws GameException
     */
    protected function guardAgainstProtection(): void
    {
        $protectionService = app(ProtectionService::class);
        if ($protectionService->isUnderProtection($this->getSelectedDominion())) {
            throw new GameException('You cannot access the forum while under protection.');
        }
    }

    protected function updateDominionForumLastRead(Dominion $dominion): void
    {
        $dominion->forum_last_read = now();
        $dominion->save();
    }
}

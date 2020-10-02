<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use DB;
use Illuminate\Http\Request;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\RankingsHelper;
use OpenDominion\Http\Requests\MessageBoard\CreatePostRequest;
use OpenDominion\Http\Requests\MessageBoard\CreateThreadRequest;
use OpenDominion\Models\MessageBoard;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\MessageBoardService;

class MessageBoardController extends AbstractController
{
    public function getIndex()
    {
        $user = Auth::getUser();
        $this->updateMessageBoardLastRead($user);

        $messageBoardService = app(MessageBoardService::class);
        $categories = $messageBoardService->getCategories();

        return view('pages.message-board.index', [
            'categories' => $categories,
            'user' => $user,
        ]);
    }

    public function getCategory(string $categorySlug)
    {
        $category = MessageBoard\Category::where('slug', '=', $categorySlug)->first();
        if ($category == null) {
            return redirect()->route('message-board');
        }

        $user = Auth::getUser();
        $this->updateMessageBoardLastRead($user);

        $messageBoardService = app(MessageBoardService::class);
        $threads = $messageBoardService->getThreads($category);

        return view('pages.message-board.category', [
            'category' => $category,
            'threads' => $threads,
            'user' => $user,
        ]);
    }

    public function getChangeAvatar()
    {
        $user = Auth::getUser();
        $rankingsHelper = app(RankingsHelper::class);

        $rankings = $rankingsHelper->getRankings();
        $userDominionIds = $user->dominions()->pluck('id');
        $previousRoundIds = Round::where('end_date', '<', now())->pluck('id');
        $previousRankings = DB::table('daily_rankings')
            ->where('rank', 1)
            ->whereIn('round_id', $previousRoundIds)
            ->whereIn('dominion_id', $userDominionIds)
            ->pluck('key');

        $defaultAvatars = collect(["ra-player", "ra-hand", "ra-beer", "ra-coffee-mug", "ra-knight-helmet", "ra-sword", "ra-shield", "ra-fairy-wand"]);

        return view('pages.message-board.avatar', [
            'user' => $user,
            'rankings' => $rankings,
            'previousRankings' => $previousRankings,
            'defaultAvatars' => $defaultAvatars,
        ]);
    }

    public function postChangeAvatar(Request $request)
    {
        $user = Auth::getUser();
        $rankingsHelper = app(RankingsHelper::class);

        $rankings = $rankingsHelper->getRankings();
        $userDominionIds = $user->dominions()->pluck('id');
        $previousRoundIds = Round::where('end_date', '<', now())->pluck('id');
        $previousRankings = DB::table('daily_rankings')
            ->where('rank', 1)
            ->whereIn('round_id', $previousRoundIds)
            ->whereIn('dominion_id', $userDominionIds)
            ->pluck('key');

        $defaultAvatars = collect(["ra-player", "ra-hand", "ra-beer", "ra-coffee-mug", "ra-knight-helmet", "ra-sword", "ra-shield", "ra-fairy-wand"]);

        try {
            $avatar = $request->get('avatar');
            $matchingRanking = collect($rankingsHelper->getRankings())->where('title_icon', $avatar)->first();
            if (!$defaultAvatars->contains($avatar) && ($matchingRanking == null || !$previousRankings->contains($matchingRanking['key']))) {
                throw new GameException('Invalid selection');
            }
            $settings = ($user->settings ?? []);
            $settings['boardavatar'] = $avatar;
            $user->settings = $settings;
            $user->save();
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your avatar has been changed');
        return redirect()->route('message-board.avatar');
    }

    public function getCreate() // getCreateThread?
    {
        $user = Auth::getUser();
        $categories = MessageBoard\Category::orderBy('role_required')->orderBy('id')->get();

        return view('pages.message-board.create', [
            'categories' => $categories,
            'user' => $user,
        ]);
    }

    public function postCreate(CreateThreadRequest $request) // postCreateThread
    {
        $user = Auth::getUser();
        $messageBoardService = app(MessageBoardService::class);

        try {
            $this->guardAgainstRepeatOffenders($user);
            $category = MessageBoard\Category::findOrFail($request->get('category'));
            $this->guardForCategory($user, $category);
            $thread = $messageBoardService->createThread(
                $user,
                $category,
                $request->get('title'),
                $request->get('body')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your thread has been created');
        return redirect()->route('message-board.thread', $thread);
    }

    public function getThread(MessageBoard\Thread $thread)
    {
        $user = Auth::getUser();
        $this->updateMessageBoardLastRead($user);

        $thread->load('user');

        return view('pages.message-board.thread', [
            'thread' => $thread,
            'user' => $user,
        ]);
    }

    public function postReply(CreatePostRequest $request, MessageBoard\Thread $thread)
    {
        $user = Auth::getUser();
        $messageBoardService = app(MessageBoardService::class);

        try {
            $this->guardAgainstRepeatOffenders($user);
            $messageBoardService->postReply(
                $user,
                $thread,
                $request->get('body')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your message has been posted');
        return redirect()->route('message-board.thread', $thread);
    }

    public function getDeletePost(MessageBoard\Post $post)
    {
        try {
            $this->guardForPost($user, $post);
        } catch (GameException $e) {
            return redirect()
                ->route('message-board')
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.message-board.delete-post', [
            'post' => $post,
        ]);
    }

    public function postDeletePost(Request $request, MessageBoard\Post $post)
    {
        $user = Auth::getUser();
        $messageBoardService = app(MessageBoardService::class);

        try {
            $this->guardForPost($user, $post);
            $messageBoardService->deletePost($user, $post);
        } catch (GameException $e) {
            return redirect()
                ->route('message-board')
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Post successfully deleted.');
        return redirect()->route('message-board.thread', $post->thread);
    }

    public function getDeleteThread(MessageBoard\Thread $thread)
    {
        try {
            $this->guardForThread($thread);
        } catch (GameException $e) {
            return redirect()
                ->route('message-board')
                ->withErrors([$e->getMessage()]);
        }

        $thread->load('user');

        return view('pages.message-board.delete-thread', [
            'thread' => $thread
        ]);
    }

    public function postDeleteThread(Request $request, MessageBoard\Thread $thread)
    {
        $user = Auth::getUser();
        $messageBoardService = app(MessageBoardService::class);

        try {
            $this->guardForThread($thread);
            $messageBoardService->deleteThread($user, $thread);
        } catch (GameException $e) {
            return redirect()
                ->route('message-board')
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Thread successfully deleted.');
        return redirect()->route('message-board');
    }

    public function getFlagPost(Request $request, MessageBoard\Post $post)
    {
        $user = Auth::getUser();
        $messageBoardService = app(MessageBoardService::class);
        $messageBoardService->flagPost($user, $post);

        $request->session()->flash('alert-success', 'Post successfully flagged for removal.');
        return redirect()->route('message-board.thread', $post->thread);
    }

    public function getFlagThread(Request $request, MessageBoard\Thread $thread)
    {
        $user = Auth::getUser();
        $messageBoardService = app(MessageBoardService::class);
        $messageBoardService->flagThread($user, $thread);

        $request->session()->flash('alert-success', 'Thread successfully flagged for removal.');
        return redirect()->route('message-board.thread', $thread);
    }

    /**
     * Throws exception if the selected user is not the post's creator.
     *
     * @param Category $category
     * @throws GameException
     */
    protected function guardForCategory(User $user, MessageBoard\Category $category): void
    {
        if ($category->role_required != null && !$user->hasRole($category->role_required)) {
            throw new GameException('No permission to moderate category.');
        }
    }

    /**
     * Throws exception if the user is not the thread's creator.
     *
     * @param Thread $thread
     * @throws GameException
     */
    protected function guardForThread(MessageBoard\Thread $thread): void
    {
        $user = Auth::getUser();

        if ($user->id !== (int)$thread->user_id) {
            throw new GameException('No permission to moderate thread.');
        }
    }

    /**
     * Throws exception if the user is not the post's creator.
     *
     * @param Post $post
     * @throws GameException
     */
    protected function guardForPost(User $user, MessageBoard\Post $post): void
    {
        if ($user->id !== (int)$post->user_id) {
            throw new GameException('No permission to moderate post.');
        }
    }

    /**
     * Throws exception if the user has abused posting privileges
     *
     * @throws GameException
     */
    protected function guardAgainstRepeatOffenders(User $user): void
    {
        $flaggedThreadCount = MessageBoard\Post::where('flagged_for_removal', true)->where('user_id', $user->id)->count();
        $flaggedPostCount = MessageBoard\Post::where('flagged_for_removal', true)->where('user_id', $user->id)->count();
        if (($flaggedThreadCount + $flaggedPostCount) >= 5) {
            throw new GameException('You have been banned from posting.');
        }
    }

    protected function updateMessageBoardLastRead(User $user): void
    {
        $user->message_board_last_read = now();
        $user->save();
    }
}

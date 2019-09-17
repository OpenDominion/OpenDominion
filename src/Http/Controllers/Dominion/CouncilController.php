<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Council\CreatePostRequest;
use OpenDominion\Http\Requests\Dominion\Council\CreateThreadRequest;
use OpenDominion\Models\Council;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\CouncilService;

class CouncilController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();
        $this->updateDominionCouncilLastRead($dominion);
        $councilService = app(CouncilService::class);

        $threads = $councilService->getThreads($dominion->realm);

        return view('pages.dominion.council.index', [
            'councilThreads' => $threads,
            'realm' => $dominion->realm,
        ]);
    }

    public function getCreate() // getCreateThread?
    {
        $dominion = $this->getSelectedDominion();
        $realm = $dominion->realm;

        return view('pages.dominion.council.create', compact(
            'realm'
        ));
    }

    public function postCreate(CreateThreadRequest $request) // postCreateThread
    {
        $dominion = $this->getSelectedDominion();
        $councilService = app(CouncilService::class);

        try {
            $thread = $councilService->createThread(
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
//            'council',
//            'create-thread',
//            $thread->title, // ?
//            null
//        ));

        $request->session()->flash('alert-success', 'Your thread has been created');
        return redirect()->route('dominion.council.thread', $thread);
    }

    public function getThread(Council\Thread $thread)
    {
        try {
            $this->guardAgainstCrossRealm($thread);
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.council')
                ->withErrors([$e->getMessage()]);
        }

        $dominion = $this->getSelectedDominion();
        $this->updateDominionCouncilLastRead($dominion);

        $thread->load(['dominion.user', 'posts.dominion.user']);

        return view('pages.dominion.council.thread', compact(
            'thread'
        ));
    }

    public function postReply(CreatePostRequest $request, Council\Thread $thread)
    {
        try {
            $this->guardAgainstCrossRealm($thread);
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.council')
                ->withErrors([$e->getMessage()]);
        }

        $dominion = $this->getSelectedDominion();
        $councilService = app(CouncilService::class);

        try {
            // todo: $post = ... and navigate to anchor with post id on page?
            $councilService->postReply($dominion, $thread, $request->get('body'));

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: fire laravel event
//        $analyticsService = app(AnalyticsService::class);
//        $analyticsService->queueFlashEvent(new Event( // todo: contract
//            'dominion.council',
//            'create-post',
//            $thread->title, // ?
//            null
//        ));

        $request->session()->flash('alert-success', 'Your message has been posted');
        return redirect()->route('dominion.council.thread', $thread);
    }

    public function getDeletePost(Council\Post $post)
    {
        try {
            $this->guardForMonarchy($post->thread);
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.council')
                ->withErrors([$e->getMessage()]);
        }

        $post->load(['dominion.user']);

        return view('pages.dominion.council.delete-post', compact(
            'post'
        ));
    }

    public function postDeletePost(Request $request, Council\Post $post)
    {
        try {
            $this->guardForMonarchy($post->thread);
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.council')
                ->withErrors([$e->getMessage()]);
        }

        $post->delete();

        $request->session()->flash('alert-success', 'Post successfully deleted.');
        return redirect()->route('dominion.council');
    }

    public function getDeleteThread(Council\Thread $thread)
    {
        try {
            $this->guardForMonarchy($thread);
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.council')
                ->withErrors([$e->getMessage()]);
        }

        $thread->load(['dominion.user', 'posts.dominion.user']);

        return view('pages.dominion.council.delete-thread', compact(
            'thread'
        ));
    }

    public function postDeleteThread(Request $request, Council\Thread $thread)
    {
        try {
            $this->guardForMonarchy($thread);
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.council')
                ->withErrors([$e->getMessage()]);
        }

        $thread->delete();

        $request->session()->flash('alert-success', 'Thread successfully deleted.');
        return redirect()->route('dominion.council');
    }

    /**
     * Throws exception if trying to view a thread outside of your realm.
     *
     * @param Council\Thread $thread
     * @throws GameException
     */
    protected function guardAgainstCrossRealm(Council\Thread $thread): void
    {
        if ($this->getSelectedDominion()->realm->id !== (int)$thread->realm_id) {
            throw new GameException('No permission to view thread.');
        }
    }

    /**
     * Throws exception if the selected dominion is not the realm's monarch.
     *
     * @param Realm $realm
     * @throws GameException
     */
    protected function guardForMonarchy(Council\Thread $thread): void
    {
        if ($this->getSelectedDominion()->id !== (int)$thread->realm->monarch_dominion_id) {
            throw new GameException('No permission to moderate council.');
        }
    }

    protected function updateDominionCouncilLastRead(Dominion $dominion): void
    {
        $dominion->council_last_read = now();
        $dominion->save();
    }
}

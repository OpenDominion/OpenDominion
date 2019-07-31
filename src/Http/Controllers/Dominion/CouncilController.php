<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Council\CreatePostRequest;
use OpenDominion\Http\Requests\Dominion\Council\CreateThreadRequest;
use OpenDominion\Models\Council;
use OpenDominion\Models\Dominion;
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
                ->route('council')
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
                ->route('council')
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
//            'council',
//            'create-post',
//            $thread->title, // ?
//            null
//        ));

        $request->session()->flash('alert-success', 'Your message has been posted');
        return redirect()->route('dominion.council.thread', $thread);
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

    protected function updateDominionCouncilLastRead(Dominion $dominion): void
    {
        $dominion->council_last_read = now();
        $dominion->save();
    }
}

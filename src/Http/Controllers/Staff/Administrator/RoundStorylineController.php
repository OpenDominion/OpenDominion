<?php

namespace OpenDominion\Http\Controllers\Staff\Administrator;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Http\Requests\Staff\Administrator\SaveStorylineRequest;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Round;
use OpenDominion\Services\GameEventService;

class RoundStorylineController extends AbstractController
{
    public function __construct(protected GameEventService $gameEventService)
    {
    }

    public function postCreate(SaveStorylineRequest $request, Round $round): RedirectResponse
    {
        $postAt = $request->filled('post_at') ? Carbon::parse($request->validated('post_at')) : null;

        $this->gameEventService->createStoryline($round, $request->validated('text'), $postAt);

        if ($postAt === null) {
            $message = 'Storyline posted to the Town Crier.';
        } elseif ($postAt->isFuture()) {
            $message = "Storyline scheduled for {$postAt->format('M d, Y H:i')} UTC.";
        } else {
            $message = "Storyline posted with a past time of {$postAt->format('M d, Y H:i')} UTC.";
        }
        $request->session()->flash('alert-success', $message);

        return redirect()->route('staff.administrator.rounds.show', $round);
    }

    public function postDelete(Request $request, Round $round, GameEvent $event): RedirectResponse
    {
        abort_unless(
            $event->type === GameEvent::TYPE_STORYLINE
                && $event->source_type === Round::class
                && (int) $event->source_id === $round->id,
            404
        );

        $event->delete();

        $request->session()->flash('alert-success', 'Storyline deleted.');

        return redirect()->route('staff.administrator.rounds.show', $round);
    }
}

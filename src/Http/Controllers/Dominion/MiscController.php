<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use Illuminate\Http\Request;
use LogicException;
use OpenDominion\Services\Dominion\SelectorService;
use RuntimeException;

// misc functions, probably could use a refactor later
class MiscController extends AbstractDominionController
{
    public function postClearNotifications()
    {
        $this->getSelectedDominion()->notifications->markAsRead();
        return redirect()->back();
    }

    public function postClosePack()
    {
        $dominion = $this->getSelectedDominion();
        $pack = $dominion->pack;

        // Only pack creator can manually close it
        if ($pack->creator_dominion_id !== $dominion->id) {
            throw new LogicException('Pack may only be closed by the creator');
        }

        $pack->closed_at = now();
        $pack->save();

        return redirect()->back();
    }

    public function postDeleteDominion(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $round = $dominion->round;
        $user = $dominion->user;

        if ($round->hasStarted()) {
            throw new RuntimeException('Unable to delete your dominion because the round has started');
        }

        if ($user->last_deleted_dominion_round === $round->number) {
            throw new RuntimeException('You already deleted your dominion once this round. You cannot delete it again.');
        }

        $dominionName = $dominion->name;

        DB::transaction(function () use ($dominion, $round, $user) {
            $dominionId = $dominion->id;

            DB::table('active_spells')->where('dominion_id', $dominionId)->delete();
            DB::table('council_posts')->where('dominion_id', $dominionId)->delete();

            // Delete threads made by Dominion, plus all replies from everyone
            foreach ($dominion->councilThreads as $thread) {
                foreach ($thread->posts as $post) {
                    $post->delete();
                }

                $thread->delete();
            }

            DB::table('daily_rankings')->where('dominion_id', $dominionId)->delete();
            DB::table('dominion_history')->where('dominion_id', $dominionId)->delete();
            DB::table('dominion_queue')->where('dominion_id', $dominionId)->delete();

            if ($dominion->pack !== null) {
                $pack = $dominion->pack;

                // If dominion is pack creator, delete the pack and un-pack other people
                if ($pack->creator_dominion_id == $dominionId) {
                    foreach ($pack->dominions as $packie) {
                        $packie->pack_id = null;
                        $packie->save();
                    }

                    $pack->delete();
                }
            }

            // game events and info ops not needed, since they can't be performed before round start (or even OOP)

            $dominion->delete();

            $user->last_deleted_dominion_round = $round->number;
            $user->save();
        });

        $selectorService = app(SelectorService::class);
        $selectorService->unsetUserSelectedDominion();

        $request->session()->flash('alert-danger', "Your dominion '{$dominionName}' has been deleted.");
        return redirect()->route('dashboard');
    }
}

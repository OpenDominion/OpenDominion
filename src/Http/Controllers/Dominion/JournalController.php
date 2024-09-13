<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Journal;
use OpenDominion\Services\JournalService;

class JournalController extends AbstractDominionController
{
    public function getJournal(Request $request, int $id = null)
    {
        $dominion = $this->getSelectedDominion();
        $journalService = app(JournalService::class);

        try {
            $journals = $journalService->getJournalEntries($dominion);
            if ($id !== null) {
                $selectedJournal = $journalService->getJournalEntry($dominion, $id);
            } else {
                $selectedJournal = null;
            }
        } catch (GameException $e) {
            return redirect()->route('dominion.journal')
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.dominion.journals.show', compact(
            'journals',
            'selectedJournal'
        ));
    }

    public function postCreate(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $journalService = app(JournalService::class);

        try {
            $journal = $journalService->createJournalEntry($dominion, $request->get('content'));
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your journal entry has been created');
        return redirect()->route('dominion.journal', $journal->id);
    }

    public function postUpdate(Request $request, Journal $journal)
    {
        $dominion = $this->getSelectedDominion();
        $journalService = app(JournalService::class);

        try {
            $journal = $journalService->updateJournalEntry($dominion, $journal, $request->get('content'));
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your journal entry has been updated');
        return redirect()->route('dominion.journal', $journal->id);
    }

    public function getDelete(Request $request, Journal $journal)
    {
        $dominion = $this->getSelectedDominion();
        $journalService = app(JournalService::class);

        try {
            $journalService->getJournalEntry($dominion, $journal->id);
        } catch (GameException $e) {
            return redirect()->route('dominion.journal')
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.dominion.journals.delete', compact(
            'journal'
        ));
    }

    public function postDelete(Request $request, Journal $journal)
    {
        $dominion = $this->getSelectedDominion();
        $journalService = app(JournalService::class);

        try {
            $journalService->deleteJournalEntry($dominion, $journal);
        } catch (GameException $e) {
            return redirect()->route('dominion.journal')
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your journal entry has been deleted');
        return redirect()->route('dominion.journal');
    }
}

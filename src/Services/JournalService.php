<?php

namespace OpenDominion\Services;

use DB;
use Illuminate\Database\Eloquent\Collection;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Journal;

class JournalService
{
    public function getJournalEntries(Dominion $dominion): Collection
    {
        return $dominion->journals()
            ->orderByDesc('created_at')
            ->get();
    }

    public function getJournalEntry(Dominion $dominion, int $id): Journal
    {
        $journal = $dominion->journals->where('id', $id)->first();
        if ($journal === null) {
            throw new GameException('Journal entry not found');
        }

        return $journal;
    }

    public function createJournalEntry(Dominion $dominion, string $content): Journal
    {
        if ($dominion->round->end_date->addDays(7) < now()) {
            throw new GameException('You can only add journal entries within 7 days of the round ending');
        }

        return Journal::create([
            'dominion_id' => $dominion->id,
            'content' => $content
        ]);
    }

    public function updateJournalEntry(Dominion $dominion, Journal $journal, string $content): Journal
    {
        if ($dominion->id !== $journal->dominion_id) {
            throw new GameException('You can only edit your own journal entries');
        }

        if ($dominion->round->end_date->addDays(30) < now()) {
            throw new GameException('You can only edit journal entries within 30 days of the round ending');
        }

        $journal->content = $content;
        $journal->save();

        return $journal;
    }

    public function deleteJournalEntry(Dominion $dominion, Journal $journal): void
    {
        if ($dominion->id !== $journal->dominion_id) {
            throw new GameException('You can only delete your own journal entries');
        }

        $journal->delete();
    }
}

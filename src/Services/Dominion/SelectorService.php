<?php

namespace OpenDominion\Services\Dominion;

use Auth;
use Illuminate\Database\Eloquent\Collection;
use LogicException;
use OpenDominion\Models\Dominion;
use RuntimeException;
use Session;

class SelectorService
{
    public const SESSION_NAME = 'selected_dominion_id';

    /** @var Dominion */
    protected $selectedDominion;

    /**
     * Returns whether the current logged in user has selected a dominion.
     *
     * @return bool
     */
    public function hasUserSelectedDominion(): bool
    {
        return (session(self::SESSION_NAME) !== null);
    }

    /**
     * Selects a dominion for the logged in user.
     *
     * @param Dominion $dominion
     * @throws LogicException
     * @throws RuntimeException
     */
    public function selectUserDominion(Dominion $dominion): void
    {
        $user = Auth::user();

        if (!$user) {
            throw new LogicException('Cannot select user dominion when not logged in');
        }

        // Check if Dominion belongs to logged in user
        if ($dominion->user_id != $user->id) {
            throw new RuntimeException('User cannot select someone else\'s Dominion');
        }

        session([self::SESSION_NAME => $dominion->id]);
    }

    /**
     * Returns the selected dominion for the logged in user, or null if user
     * hasn't selected any.
     *
     * @return Dominion|null
     */
    public function getUserSelectedDominion(): ?Dominion
    {
        $dominionId = session(self::SESSION_NAME);

        if ($dominionId === null) {
            return null;
        }

        if ($this->selectedDominion === null || ($dominionId !== $this->selectedDominion->id)) {
            $this->selectedDominion = Dominion::with([
                'queues',
                'race',
                'race.perks',
                'race.units',
                'race.units.perks',
                'realm',
                'realm.wonders',
                'realm.wonders.perks',
                'spells',
                'spells.perks',
                'techs',
                'techs.perks',
            ])->findOrFail($dominionId);
        }

        // Track hourly access activity
        // TODO: Swap 47 with actual round length
        if ($this->selectedDominion && $this->selectedDominion->round->isActive()) {
            // Generate 1128 bit string of 0s
            if (!$this->selectedDominion->hourly_activity) {
                $roundBinary = '';
                $dayBinary = '';
                foreach(range(1, 24) as $n) {
                    $dayBinary .= '0';
                }
                foreach(range(1, 47) as $n) {
                    $roundBinary .= $dayBinary;
                }
                $this->selectedDominion->hourly_activity = $roundBinary;
            }

            // Set bit for this day/hour to 1
            $index = (int) $this->selectedDominion->round->getTick();
            $hourlyActivity = $this->selectedDominion->hourly_activity;
            if ($hourlyActivity !== null && is_string($hourlyActivity) && $index >= 0 && $index < strlen($hourlyActivity) && $hourlyActivity[$index] === '0') {
                $hourlyActivity[$index] = '1';
                $this->selectedDominion->hourly_activity = $hourlyActivity;
                $this->selectedDominion->save();
            }
        }

        return $this->selectedDominion;
    }

    /**
     * Unsets the selected dominion for the logged in user.
     */
    public function unsetUserSelectedDominion(): void
    {
        Session::forget(self::SESSION_NAME);
    }

    /**
     * Tries to auto-select a dominion for the logged in user.
     *
     * Auto-select only works when the user currently has only one active dominion.
     *
     * @return Dominion|null The auto-selected dominion
     * @throws LogicException
     * @throws RuntimeException
     */
    public function tryAutoSelectDominionForAuthUser(): ?Dominion
    {
        if ($this->hasUserSelectedDominion()) {
            return $this->getUserSelectedDominion();
        }

        $user = Auth::user();

        if (!$user) {
            throw new LogicException('Cannot auto-select user dominion when not logged in');
        }

        /** @var Collection $activeDominions */
        $activeDominions = $user->dominions()->active()->get();

        if ($activeDominions->count() == 0) {
            // Rounds that haven't started yet
            $activeDominions = Dominion::with('round')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->get()
                ->filter(function ($dominion) {
                    if ($dominion->round->start_date > now()) {
                        return $dominion;
                    }
                });
        }

        if ($activeDominions->count() == 0) {
            return null;
        }

        $dominion = $activeDominions->first();

        $this->selectUserDominion($dominion);

        return $dominion;
    }
}

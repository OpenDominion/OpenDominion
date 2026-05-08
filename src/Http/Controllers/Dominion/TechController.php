<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Http\Requests\Dominion\Actions\TechActionRequest;
use OpenDominion\Models\DominionTech;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Tech;
use OpenDominion\Services\Dominion\Actions\TechActionService;

class TechController extends AbstractDominionController
{
    public function getTechs()
    {
        return view('pages.dominion.techs', [
            'productionCalculator' => app(ProductionCalculator::class),
            'techCalculator' => app(TechCalculator::class),
            'techHelper' => app(TechHelper::class),
        ]);
    }

    public function postTechs(TechActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $techActionService = app(TechActionService::class);

        try {
            $result = $techActionService->unlock(
                $dominion,
                $request->get('key')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.techs');
    }

    public function postTemporaryTech(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        try {
            // Check realm controls Planar Gates
            if ($dominion->getWonderPerkValue('temporary_tech') <= 0) {
                throw new GameException('Your realm does not control a wonder that grants a temporary tech.');
            }

            $techKey = $request->get('tech');
            if (!$techKey) {
                throw new GameException('You must select a tech.');
            }

            // Validate tech exists and is not Urg Smash Technique
            $tech = Tech::where('version', $dominion->round->tech_version)
                ->where('key', $techKey)
                ->first();

            if ($tech === null) {
                throw new GameException('Invalid tech selected.');
            }

            if ($tech->key === 'tech_7_5') {
                throw new GameException('Urg Smash Technique cannot be selected as a temporary tech.');
            }

            // Check not already permanently unlocked
            $permanentlyUnlocked = $dominion->techs->filter(function ($t) use ($tech) {
                return $t->id === $tech->id && $t->pivot->source_id === null;
            });

            if ($permanentlyUnlocked->isNotEmpty()) {
                throw new GameException('You have already permanently unlocked this tech.');
            }

            // Find the RoundWonder for Planar Gates
            $roundWonder = RoundWonder::whereHas('wonder', function ($query) {
                $query->where('key', 'planar_gates');
            })
                ->where('round_id', $dominion->round_id)
                ->where('realm_id', $dominion->realm_id)
                ->first();

            if ($roundWonder === null) {
                throw new GameException('Your realm does not control the Planar Gates.');
            }

            // Check cooldown on existing temporary tech
            $existingTemp = DominionTech::where('dominion_id', $dominion->id)
                ->where('source_type', RoundWonder::class)
                ->where('source_id', $roundWonder->id)
                ->first();

            if ($existingTemp !== null) {
                $selectedAt = $existingTemp->created_at->startOfHour();
                $cooldownEnd = $selectedAt->copy()->addHours(96);
                if (now()->lt($cooldownEnd)) {
                    $hoursLeft = now()->diffInHours($cooldownEnd, false) + 1;
                    throw new GameException(sprintf(
                        'You must wait %d more hours before changing your temporary tech.',
                        $hoursLeft
                    ));
                }
            }

            // Delete any existing temporary tech from this source
            DominionTech::where('dominion_id', $dominion->id)
                ->where('source_type', RoundWonder::class)
                ->where('source_id', $roundWonder->id)
                ->delete();

            // Insert new temporary tech
            DominionTech::create([
                'dominion_id' => $dominion->id,
                'tech_id' => $tech->id,
                'source_type' => RoundWonder::class,
                'source_id' => $roundWonder->id,
            ]);

            // Clear cached techs relationship
            $dominion->load('techs');

        } catch (GameException $e) {
            return redirect()->route('dominion.techs')
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', sprintf(
            'You have selected %s as your temporary tech.',
            $tech->name
        ));

        return redirect()->route('dominion.techs');
    }
}

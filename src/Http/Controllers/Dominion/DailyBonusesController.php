<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\AutomationActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\AutomationReorderRequest;
use OpenDominion\Http\Requests\Dominion\Actions\AutomationTemplateRequest;
use OpenDominion\Http\Requests\Dominion\Actions\AutomationTickCopyRequest;
use OpenDominion\Http\Requests\Dominion\Actions\DailyBonusesLandActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\DailyBonusesPlatinumActionRequest;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;
use OpenDominion\Services\Dominion\AutomationService;
use OpenDominion\Services\Dominion\LogParserService;

class DailyBonusesController extends AbstractDominionController
{
    public function getBonuses(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        $logParserService = app(LogParserService::class);
        $log = $logParserService->writeLog($dominion);

        return view('pages.dominion.bonuses', [
            'allowedActions' => AutomationService::DAILY_ACTIONS,
            'log' => $log
        ]);
    }

    public function postBonusesPlatinum(DailyBonusesPlatinumActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $dailyBonusesActionService = app(DailyBonusesActionService::class);

        try {
            $result = $dailyBonusesActionService->claimPlatinum($dominion);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.bonuses');
    }

    public function postBonusesLand(DailyBonusesLandActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $dailyBonusesActionService = app(DailyBonusesActionService::class);

        try {
            $result = $dailyBonusesActionService->claimLand($dominion);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.bonuses');
    }

    public function getAutomatedActions(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $buildingHelper = app(BuildingHelper::class);
        $landHelper = app(LandHelper::class);
        $spellHelper = app(SpellHelper::class);
        $unitHelper = app(UnitHelper::class);
        $automationService = app(AutomationService::class);

        $buildings = $buildingHelper->getBuildingTypes();
        $landTypes = $landHelper->getLandTypes();
        $spells = $spellHelper->getSpells($dominion->race, 'self')
            ->forget(['amplify_magic', 'ares_call', 'fools_gold'])
            ->sortBy('key');
        $unitTypes = $unitHelper->getUnitTypes();
        $quickFillOptions = [
            'race' => $dominion->race->name,
            'storageKey' => 'opendominion.automationQuickFills.v1.user.' . $request->user()->id,
            'units' => collect($unitTypes)->map(function ($unitType) use ($unitHelper, $dominion) {
                return ['key' => $unitType, 'label' => $unitHelper->getUnitName($unitType, $dominion->race)];
            })->values(),
            'buildings' => collect($buildings)->map(function ($building) use ($buildingHelper) {
                return ['key' => $building, 'label' => $buildingHelper->getBuildingName($building)];
            })->values(),
            'landTypes' => collect($landTypes)->map(function ($landType) {
                return ['key' => $landType, 'label' => ucwords($landType)];
            })->values(),
            'spells' => $spells->map(function ($spell) {
                return ['key' => $spell->key, 'label' => $spell->name];
            })->values(),
            'bonuses' => collect([
                ['key' => 'land', 'label' => 'Land'],
                ['key' => 'platinum', 'label' => 'Platinum'],
            ]),
        ];

        return view('pages.dominion.automation', [
            'buildingHelper' => $buildingHelper,
            'spellHelper' => $spellHelper,
            'unitHelper' => $unitHelper,
            'allowedActions' => AutomationService::DAILY_ACTIONS,
            'automationTemplates' => $automationService->getTemplates($dominion),
            'buildings' => $buildings,
            'landTypes' => $landTypes,
            'maxActionsPerTick' => AutomationService::MAX_ACTIONS_PER_TICK,
            'maxScheduleHours' => AutomationService::MAX_SCHEDULE_HOURS,
            'maxTemplateSlots' => AutomationService::MAX_TEMPLATE_SLOTS,
            'quickFillOptions' => $quickFillOptions,
            'spells' => $spells,
            'unitTypes' => $unitTypes,
        ]);
    }

    public function postAutomatedActions(AutomationActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        if ($dominion->protection_ticks_remaining) {
            $request->session()->flash('alert-danger', 'You cannot schedule any actions while you have protection ticks remaining.');
            return redirect()->route('dominion.bonuses.actions');
        }
        $automationService = app(AutomationService::class);

        $config = [
            'tick' => $request->get('tick'),
            'value' => [
                'action' => $request->get('action'),
                'key' => $request->get('key'),
                'key2' => $request->get('key2'),
                'amount' => $request->get('amount')
            ]
        ];

        try {
            $automationService->setConfig($dominion, $config);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Action was successfully scheduled.');
        return redirect()->route('dominion.bonuses.actions')->withInput($request->only('tick'));
    }

    public function postDeleteAutomatedAction(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $automationService = app(AutomationService::class);

        try {
            $automationService->deleteAction($dominion, $request->get('tick'), $request->get('key'));
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Action was successfully deleted.');
        return redirect()->route('dominion.bonuses.actions');
    }

    public function postReorderAutomatedAction(AutomationReorderRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $automationService = app(AutomationService::class);

        try {
            $automationService->moveAction(
                $dominion,
                (int) $request->get('tick'),
                (int) $request->get('key'),
                (int) $request->get('target_key')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Action order was updated.');
        return redirect()->route('dominion.bonuses.actions');
    }

    public function postEditAutomatedAction(AutomationActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $automationService = app(AutomationService::class);

        $value = [
            'action' => $request->get('action'),
            'key' => $request->get('key'),
            'key2' => $request->get('key2'),
            'amount' => $request->get('amount'),
        ];

        try {
            $automationService->editAction($dominion, (int) $request->get('tick'), (int) $request->get('edit_key'), $value);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Action was successfully updated.');
        return redirect()->route('dominion.bonuses.actions');
    }

    public function postDuplicateAutomatedAction(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $automationService = app(AutomationService::class);

        try {
            $automationService->duplicateAction(
                $dominion,
                (int) $request->get('source_tick'),
                (int) $request->get('source_key'),
                (int) $request->get('target_tick')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Action was successfully duplicated.');
        return redirect()->route('dominion.bonuses.actions');
    }

    public function postCopyAutomatedTick(AutomationTickCopyRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $automationService = app(AutomationService::class);

        try {
            $automationService->copyTick(
                $dominion,
                (int) $request->get('source_tick'),
                $request->get('target_ticks')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'The complete tick was successfully copied.');
        return redirect()->route('dominion.bonuses.actions');
    }

    public function postAutomationTemplate(AutomationTemplateRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $automationService = app(AutomationService::class);
        $operation = $request->get('operation');
        $slot = (int) $request->get('slot');

        try {
            if ($operation === 'save') {
                $automationService->saveTemplate($dominion, $slot, $request->get('name'));
                $message = 'Automation template saved.';
            } elseif ($operation === 'load') {
                $automationService->loadTemplate($dominion, $slot, $request->get('mode'));
                $message = 'Automation template loaded relative to the current tick.';
            } else {
                $automationService->deleteTemplate($dominion, $slot);
                $message = 'Automation template deleted.';
            }
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $message);
        return redirect()->route('dominion.bonuses.actions');
    }

    public function postClearAutomatedActions(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $automationService = app(AutomationService::class);

        try {
            $automationService->clearTick($dominion, (int) $request->get('tick'));
        } catch (GameException $e) {
            return redirect()->back()
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'All actions for this tick were cleared.');
        return redirect()->route('dominion.bonuses.actions');
    }
}

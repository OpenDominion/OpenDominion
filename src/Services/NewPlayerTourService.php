<?php

namespace OpenDominion\Services;

use Illuminate\Http\Request;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\User;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;

class NewPlayerTourService
{
    public const SETTING_KEY = 'new_player_tour';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';

    private const PHASE_FOUNDATION = 'foundation';
    private const PHASE_ACTIVE = 'active';
    private const LAST_FOUNDATION_STEP = 'magic';

    private const STEPS = [
        'status' => [
            'phase' => self::PHASE_FOUNDATION,
            'navigation_stage' => 0,
            'label' => 'Status',
            'route' => 'dominion.status',
            'matches' => ['dominion.status'],
            'target' => 'status',
            'title' => 'Your dominion at a glance',
            'body' => 'Status is your command summary. The round goal is to finish with the most land: grow peacefully by exploring while maintaining your defenses, or gain land through conquest.',
        ],
        'quick_start' => [
            'phase' => self::PHASE_FOUNDATION,
            'navigation_stage' => 0,
            'label' => 'Quick Start',
            'route' => 'dominion.status',
            'matches' => ['dominion.status', 'dominion.misc.restart'],
            'target' => 'quick-start',
            'nav' => 'status',
            'title' => 'Choose your guided start',
            'body' => 'Restart now to choose your race and select Quick Start. Your race defines your units, perks, spells, and strategic strengths. Quick Start begins with 500 acres and uses built-in checkpoints to guide the rest of protection.',
            'completion' => 'quick_start',
            'objective' => 'Open Restart & Rename, choose a race, select Quick Start, then press Restart.',
        ],
        'advisors' => [
            'phase' => self::PHASE_FOUNDATION,
            'navigation_stage' => 1,
            'label' => 'Advisors',
            'route' => 'dominion.advisors',
            'matches' => ['dominion.advisors*'],
            'target' => 'resources-overview',
            'title' => 'Read the details',
            'body' => 'Advisors break down production, military, magic, rankings, and statistics. The overview above tracks essentials such as land, platinum, food, lumber, mana, draftees, and defense.',
        ],
        'starting_buildings' => [
            'phase' => self::PHASE_FOUNDATION,
            'navigation_stage' => 4,
            'label' => 'Starting Buildings',
            'route' => 'dominion.protection.buildings',
            'matches' => ['dominion.protection.buildings'],
            'target' => 'starting-buildings',
            'nav' => 'construction',
            'title' => 'Build your opening',
            'body' => 'Choose starting buildings for every acre. Homes support population; farms prevent starvation; resource buildings fund what comes next. You will also need barracks for military and mana plus wizards before you can use magic.',
            'completion' => 'starting_buildings',
            'objective' => 'Allocate every starting acre, then press Build.',
        ],
        'daily_bonus' => [
            'phase' => self::PHASE_FOUNDATION,
            'navigation_stage' => 4,
            'label' => 'Daily Bonus',
            'route' => 'dominion.bonuses',
            'matches' => ['dominion.bonuses'],
            'target' => 'daily-bonus',
            'title' => 'Collect your daily bonuses',
            'body' => 'Claim both bonuses each game day. Land directly grows your dominion; platinum and research help fund exploration, construction, military, and technology.',
            'completion' => 'daily_bonuses',
            'objective' => 'Claim both the Land Bonus and Platinum Bonus.',
        ],
        'discord' => [
            'phase' => self::PHASE_FOUNDATION,
            'navigation_stage' => 4,
            'label' => 'Community',
            'route' => 'dominion.bonuses',
            'matches' => ['dominion.bonuses'],
            'target' => 'discord-community',
            'title' => 'Join the OpenDominion Discord',
            'body' => 'Protection is a simulation, and questions are expected. Join Discord now so experienced players can help with your build, explain an unfamiliar choice, and later connect you with your realm mates.',
        ],
        'explore' => [
            'phase' => self::PHASE_FOUNDATION,
            'navigation_stage' => 4,
            'label' => 'Explore Land',
            'route' => 'dominion.explore',
            'matches' => ['dominion.explore'],
            'target' => 'explore',
            'title' => 'Send your first exploration',
            'body' => 'Exploring is how you grow without invading. It costs platinum and draftees per acre, and the land arrives over time. Growth is safest when your defense grows with it.',
            'completion' => 'explore',
            'objective' => 'Send at least one acre on exploration.',
        ],
        'construction' => [
            'phase' => self::PHASE_FOUNDATION,
            'navigation_stage' => 4,
            'label' => 'Construction',
            'route' => 'dominion.construct',
            'matches' => ['dominion.construct'],
            'target' => 'construction',
            'title' => 'Queue your next buildings',
            'body' => 'Explored land arrives barren. Construction turns it into the homes, farms, barracks, and resource buildings your plan needs. Ordinary construction costs platinum and lumber and completes after 12 hours.',
            'completion' => 'construct',
            'objective' => 'Submit at least one construction order.',
        ],
        'military' => [
            'phase' => self::PHASE_FOUNDATION,
            'navigation_stage' => 5,
            'label' => 'Military',
            'route' => 'dominion.military',
            'matches' => ['dominion.military'],
            'target' => 'military',
            'title' => 'Train your first units',
            'body' => 'Draftees become trained units here. Defensive power protects your land; offensive power and boats let you conquer it. If you cannot train yet, use protection hours to gain resources and construct the support you need, then return—never grow beyond what you can defend.',
            'completion' => 'train',
            'objective' => 'Advance and prepare as needed, then submit at least one military training order.',
        ],
        'magic' => [
            'phase' => self::PHASE_FOUNDATION,
            'navigation_stage' => 6,
            'label' => 'Magic',
            'route' => 'dominion.magic',
            'matches' => ['dominion.magic'],
            'target' => 'self-spells',
            'title' => 'Maintain your self spells',
            'body' => "At minimum, prepare to keep Midas Touch active whenever possible for its platinum bonus. Depending on your strategy, you will likely keep Ares' Call active always or frequently for defense. If you cannot cast yet, advance protection while building mana production and training wizards, then return.",
            'completion' => 'self_spell',
            'objective' => 'Successfully cast one self spell.',
        ],
        'realm' => [
            'phase' => self::PHASE_ACTIVE,
            'navigation_stage' => 7,
            'label' => 'Realms',
            'route' => 'dominion.realm',
            'matches' => ['dominion.realm'],
            'target' => 'realm',
            'title' => 'Meet your team',
            'body' => 'Your realm is your team. Coordinate plans, share information, and help cover one another as the round develops.',
        ],
        'rankings' => [
            'phase' => self::PHASE_ACTIVE,
            'navigation_stage' => 8,
            'label' => 'Rankings',
            'route' => 'dominion.rankings',
            'matches' => ['dominion.rankings'],
            'target' => 'rankings',
            'title' => 'Keep the objective in sight',
            'body' => 'Rankings show how dominions compare. The central objective is simple: end the round with the most land. Everything you have learned helps you choose how to get there.',
        ],
    ];

    public function __construct(private ProtectionService $protectionService)
    {
    }

    public function defaultState(bool $newAccount = true): array
    {
        return [
            'status' => self::STATUS_ACTIVE,
            'step' => array_key_first(self::STEPS),
            'new_account' => $newAccount,
        ];
    }

    public function getState(User $user): ?array
    {
        $state = $user->getSetting(self::SETTING_KEY);
        if (!is_array($state) || !isset($state['status'])) {
            return null;
        }

        return $state;
    }

    public function getActiveStep(User $user): ?string
    {
        $state = $this->getState($user);
        if (($state['status'] ?? null) !== self::STATUS_ACTIVE || !isset(self::STEPS[$state['step'] ?? ''])) {
            return null;
        }

        return $state['step'];
    }

    public function getStage(User $user, ?Dominion $dominion = null): ?int
    {
        $step = $this->getActiveStep($user);
        if ($step === null) {
            return null;
        }

        if ($dominion !== null && !$this->isStepAvailable($step, $dominion)) {
            return null;
        }

        return self::STEPS[$step]['navigation_stage'];
    }

    public function getViewData(User $user, ?Dominion $dominion, Request $request): array
    {
        $stepKey = $this->getActiveStep($user);
        if ($stepKey === null || $dominion === null) {
            return ['newPlayerTour' => null, 'onboardingStage' => null];
        }

        $keys = array_keys(self::STEPS);
        $index = (int)array_search($stepKey, $keys, true);
        if (!$this->isStepAvailable($stepKey, $dominion)) {
            return $this->getPausedViewData($stepKey, $dominion, $request);
        }

        $step = self::STEPS[$stepKey];
        $protectionActionProgress = match ($stepKey) {
            'explore' => $this->getProtectionActionProgress($dominion, HistoryService::EVENT_ACTION_EXPLORE),
            'construction' => $this->getProtectionActionProgress($dominion, HistoryService::EVENT_ACTION_CONSTRUCT),
            default => null,
        };
        $requiresQuickStart = $stepKey === 'quick_start' && $this->isNewAccountGuide($user);
        if ($stepKey === 'quick_start' && !$requiresQuickStart) {
            unset($step['completion'], $step['objective']);
            $step['title'] = 'Quick Start for new dominions';
            $step['body'] = 'Quick Start is the recommended guided opening for a new dominion. Because you restarted the field guide later, you can continue without resetting your current dominion.';
        }
        $startingBuildingsSelected = $stepKey === 'starting_buildings' && $this->hasStartingBuildingsSelection($dominion);
        if ($startingBuildingsSelected && $dominion->isBuildingPhase()) {
            $step['target'] = 'protection-confirm';
            $step['title'] = 'Confirm your opening';
            $step['body'] = 'Your starting layout is saved. OpenDominion lets you revise it until you confirm the choice and begin the first protection hour.';
            $step['objective'] = 'Press Next » in the protection banner to confirm your buildings.';
        }
        if ($protectionActionProgress !== null && $protectionActionProgress['performed'] && !$protectionActionProgress['advanced']) {
            $step['target'] = 'protection-confirm';
            $step['title'] = $stepKey === 'explore' ? 'Bring your explored acres home' : 'Finish your construction';
            $step['body'] = $stepKey === 'explore'
                ? 'Your exploration order is queued. Advance Quick Start so the incoming acres arrive before you decide what to build on them.'
                : 'Your building order is queued. Advance Quick Start so construction completes and your dominion is ready for military training.';
            $step['objective'] = 'Press Next » in the protection banner to complete this stage.';
        }
        $step['key'] = $stepKey;
        $step['nav'] = $step['nav'] ?? $stepKey;
        $step['number'] = $index + 1;
        $step['total'] = count($keys);
        $step['phase_label'] = $step['phase'] === self::PHASE_ACTIVE ? 'ACTIVE PLAY' : 'FIELD GUIDE';
        $step['paused'] = false;
        $step['quest'] = isset($step['completion']);
        $step['satisfied'] = !$step['quest'] ? true : ($stepKey === 'starting_buildings'
            ? $startingBuildingsSelected && !$dominion->isBuildingPhase()
            : ($protectionActionProgress['advanced'] ?? $this->isStepSatisfied($stepKey, $dominion)));
        if ($requiresQuickStart && $step['satisfied']) {
            $step['title'] = 'Quick Start is ready';
            $step['body'] = 'Your race and Quick Start choice are recorded. Continue to allocate all 500 starting acres; the protection banner will then guide you through the remaining checkpoints.';
        }
        $step['objective'] = $step['objective'] ?? null;
        $step['action_url'] = match ($stepKey) {
            'quick_start' => $requiresQuickStart && !$step['satisfied'] ? route('dominion.misc.restart') : null,
            'discord' => config('app.discord_invite_link') ?: 'https://discord.gg/mFk2wZT',
            default => null,
        };
        $step['action_label'] = match ($stepKey) {
            'quick_start' => 'Open Restart & Rename',
            'discord' => 'Join Discord',
            default => null,
        };
        $step['action_external'] = $stepKey === 'discord';
        $step['progress_percent'] = (($index + 1) / count($keys)) * 100;
        $step['progress_items'] = $this->getProgressItems($index, $dominion, $step['satisfied']);
        $step['url'] = $this->getStepUrl($stepKey, $dominion);
        $step['is_current_page'] = $request->routeIs(...$step['matches']);

        return ['newPlayerTour' => $step, 'onboardingStage' => $this->getStage($user, $dominion)];
    }

    public function advance(User $user, ?Dominion $dominion): bool
    {
        $step = $this->getActiveStep($user);
        if ($step === null || $dominion === null) {
            return false;
        }

        $requiresCompletion = $step !== 'quick_start' || $this->isNewAccountGuide($user);
        if (!$this->isStepAvailable($step, $dominion) || ($requiresCompletion && !$this->isStepSatisfied($step, $dominion))) {
            return false;
        }

        $keys = array_keys(self::STEPS);
        $index = array_search($step, $keys, true);
        if ($index === count($keys) - 1) {
            $this->setState($user, ['status' => self::STATUS_COMPLETED, 'step' => $step]);
            return true;
        }

        $this->setState($user, ['status' => self::STATUS_ACTIVE, 'step' => $keys[$index + 1]]);
        return true;
    }

    public function goBack(User $user): void
    {
        $step = $this->getActiveStep($user);
        if ($step === null) {
            return;
        }

        $keys = array_keys(self::STEPS);
        $index = array_search($step, $keys, true);
        $this->setState($user, ['status' => self::STATUS_ACTIVE, 'step' => $keys[max(0, $index - 1)]]);
    }

    public function skip(User $user): void
    {
        $step = $this->getActiveStep($user) ?? array_key_first(self::STEPS);
        $this->setState($user, ['status' => self::STATUS_SKIPPED, 'step' => $step]);
    }

    public function restart(User $user): void
    {
        $this->setState($user, $this->defaultState(false));
    }

    public function getCurrentStepUrl(User $user, ?Dominion $dominion): ?string
    {
        $step = $this->getActiveStep($user);
        return $step === null || $dominion === null || !$this->isStepAvailable($step, $dominion)
            ? null
            : $this->getStepUrl($step, $dominion);
    }

    private function getPausedViewData(string $stepKey, Dominion $dominion, Request $request): array
    {
        $keys = array_keys(self::STEPS);
        $index = (int)array_search($stepKey, $keys, true);
        $foundationIndex = (int)array_search(self::LAST_FOUNDATION_STEP, $keys, true);
        $lastFoundation = self::STEPS[self::LAST_FOUNDATION_STEP];

        $step = [
            'key' => $stepKey,
            'nav' => self::LAST_FOUNDATION_STEP,
            'target' => $lastFoundation['target'],
            'matches' => $lastFoundation['matches'],
            'label' => 'Active play',
            'title' => 'Protection chapter complete',
            'body' => 'Keep training troops and complete your remaining protection hours. Feel free to explore the now-unlocked pages and get familiar with the game, and ask questions in Discord whenever you need help. The guide will resume with Realms and Rankings after protection ends and realm assignment is complete.',
            'number' => $foundationIndex + 1,
            'total' => count($keys),
            'phase_label' => 'CHAPTER COMPLETE',
            'paused' => true,
            'quest' => false,
            'satisfied' => true,
            'objective' => null,
            'action_url' => route('dominion.misc.restart'),
            'action_label' => 'Restart or Rename',
            'action_external' => false,
            'progress_percent' => (($foundationIndex + 1) / count($keys)) * 100,
            'progress_items' => $this->getProgressItems($index, $dominion),
            'url' => null,
            'is_current_page' => $request->routeIs(...$lastFoundation['matches']),
        ];

        return ['newPlayerTour' => $step, 'onboardingStage' => null];
    }

    private function getProgressItems(int $currentIndex, Dominion $dominion, bool $currentSatisfied = false): array
    {
        $items = [];
        foreach (array_keys(self::STEPS) as $index => $key) {
            $step = self::STEPS[$key];
            if ($index < $currentIndex) {
                $state = 'completed';
            } elseif (!$this->isStepAvailable($key, $dominion)) {
                $state = 'locked';
            } elseif ($index === $currentIndex) {
                $state = isset($step['completion']) && $currentSatisfied ? 'ready' : 'current';
            } else {
                $state = 'upcoming';
            }

            $items[] = ['label' => $step['label'], 'state' => $state];
        }

        return $items;
    }

    private function isStepAvailable(string $step, Dominion $dominion): bool
    {
        if (self::STEPS[$step]['phase'] === self::PHASE_FOUNDATION) {
            return true;
        }

        return !$this->protectionService->isUnderProtection($dominion)
            && $dominion->round->hasAssignedRealms();
    }

    private function isStepSatisfied(string $step, Dominion $dominion): bool
    {
        $completion = self::STEPS[$step]['completion'] ?? null;
        if ($completion === null) {
            return true;
        }

        return match ($completion) {
            'quick_start' => data_get(
                $dominion->history()
                    ->where('event', HistoryService::EVENT_ACTION_RESTART)
                    ->latest('id')
                    ->first(['delta']),
                'delta.action'
            ) === 'quick',
            'starting_buildings' => !$dominion->isBuildingPhase() && $this->hasStartingBuildingsSelection($dominion),
            'daily_bonuses' => $dominion->history()
                ->where('event', HistoryService::EVENT_ACTION_DAILY_BONUS)
                ->where('delta->daily_land', true)
                ->exists()
                && $dominion->history()
                    ->where('event', HistoryService::EVENT_ACTION_DAILY_BONUS)
                    ->where('delta->daily_platinum', true)
                    ->exists(),
            'explore' => $this->getProtectionActionProgress($dominion, HistoryService::EVENT_ACTION_EXPLORE)['advanced'],
            'construct' => $this->getProtectionActionProgress($dominion, HistoryService::EVENT_ACTION_CONSTRUCT)['advanced'],
            'train' => $dominion->history()
                ->where('event', HistoryService::EVENT_ACTION_TRAIN)
                ->exists(),
            'self_spell' => $dominion->history()
                ->where('event', HistoryService::EVENT_ACTION_CAST_SPELL)
                ->whereRaw("JSON_LENGTH(JSON_EXTRACT(delta, '$.queue.active_spells')) > 0")
                ->exists(),
            default => false,
        };
    }

    private function hasStartingBuildingsSelection(Dominion $dominion): bool
    {
        return $dominion->history()
            ->where('event', HistoryService::EVENT_TICK)
            ->where('delta->action', 'starting_buildings')
            ->exists();
    }

    private function getProtectionActionProgress(Dominion $dominion, string $event): array
    {
        $actionId = $dominion->history()
            ->where('event', $event)
            ->latest('id')
            ->value('id');

        return [
            'performed' => $actionId !== null,
            'advanced' => $actionId !== null
                && (
                    !$this->protectionService->isUnderProtection($dominion)
                    || $dominion->history()
                        ->where('event', HistoryService::EVENT_ACTION_PROTECTION_ADVANCE_TICK)
                        ->where('id', '>', $actionId)
                        ->exists()
                ),
        ];
    }

    private function getStepUrl(string $step, Dominion $dominion): string
    {
        if ($step === 'starting_buildings' && $dominion->isBuildingPhase()) {
            return route('dominion.protection.buildings');
        }

        return route(self::STEPS[$step]['route']);
    }

    private function isNewAccountGuide(User $user): bool
    {
        return (bool)($this->getState($user)['new_account'] ?? true);
    }

    private function setState(User $user, array $state): void
    {
        $settings = $user->settings ?? [];
        $settings[self::SETTING_KEY] = array_merge($this->getState($user) ?? [], $state);
        $user->settings = $settings;
        $user->save();
    }
}

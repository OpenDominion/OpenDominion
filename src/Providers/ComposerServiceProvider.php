<?php

namespace OpenDominion\Providers;

use Auth;
use Cache;
use DB;
use Illuminate\Contracts\View\View;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\Council;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Forum;
use OpenDominion\Models\MessageBoard;
use OpenDominion\Services\Dominion\SelectorService;

class ComposerServiceProvider extends AbstractServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer('layouts.topnav', function (View $view) {
            $view->with('selectorService', app(SelectorService::class));
        });

        view()->composer('partials.main-sidebar', function (View $view) {
            $selectorService = app(SelectorService::class);

            if (!$selectorService->hasUserSelectedDominion()) {
                return;
            }

            $user = Auth::getUser();
            /** @var Dominion $selectedDominion */
            $selectedDominion = $selectorService->getUserSelectedDominion();

            $councilLastRead = $selectedDominion->council_last_read ?? $selectedDominion->round->start_date;
            $councilUnreadCount = $selectedDominion->realm->councilThreads()
                ->where('last_activity', '>', $councilLastRead)
                ->withCount(['posts' => function ($query) use ($councilLastRead) {
                    $query->where('created_at', '>', $councilLastRead);
                }])
                ->get()
                ->sum('posts_count');
            $view->with('councilUnreadCount', $councilUnreadCount);

            $forumLastRead = $selectedDominion->forum_last_read ?? $selectedDominion->round->start_date;
            $forumUnreadCount = $selectedDominion->round->forumThreads()
                ->where('last_activity', '>', $forumLastRead)
                ->withCount(['posts' => function ($query) use ($forumLastRead) {
                    $query->where('created_at', '>', $forumLastRead);
                }])
                ->get()
                ->sum('posts_count');
            $view->with('forumUnreadCount', $forumUnreadCount);

            $messageBoardLastRead = $user->message_board_last_read ?? $user->created_at;
            $messageBoardUnreadCount = MessageBoard\Thread::query()
                ->where('last_activity', '>', $messageBoardLastRead)
                ->withCount(['posts' => function ($query) use ($messageBoardLastRead) {
                    $query->where('created_at', '>', $messageBoardLastRead);
                }])
                ->get()
                ->sum('posts_count');
            $view->with('messageBoardUnreadCount', $messageBoardUnreadCount);

            $activeSpells = DB::table('active_spells')
                ->where('dominion_id', $selectedDominion->id)
                ->where('duration', '>', 0)
                ->get([
                    'cast_by_dominion_id'
                ]);

            $activeSelfSpells = 0;
            $activeHostileSpells = 0;
            foreach($activeSpells as $activeSpell) {
                if($activeSpell->cast_by_dominion_id === $selectedDominion->id) {
                    $activeSelfSpells++;
                }
                else {
                    $activeHostileSpells++;
                }
            }

            $view->with('activeSelfSpells', $activeSelfSpells);
            $view->with('activeHostileSpells', $activeHostileSpells);

            // Show icon for techs
            $techCalculator = app(TechCalculator::class);
            $techCost = $techCalculator->getTechCost($selectedDominion);
            $unlockableTechCount = floor($selectedDominion->resource_tech / $techCost);
            $view->with('unlockableTechCount', $unlockableTechCount);

            $landCalculator = app(LandCalculator::class);

            $barrenLand = $landCalculator->getTotalBarrenLand($selectedDominion);
            $view->with('barrenLand', $barrenLand);

            $unseenWonders = DB::table('round_wonders')
                ->where('round_id', $selectedDominion->round_id)
                ->where('created_at', '>', $selectedDominion->wonders_last_seen ?? $selectedDominion->round->start_date)
                ->count();

            $view->with('unseenWonders', $unseenWonders);

            $unseenGameEvents = DB::table('game_events')
                ->where('round_id', $selectedDominion->round_id)
                ->where('created_at', '>', $selectedDominion->town_crier_last_seen ?? $selectedDominion->round->start_date)
                ->count();

            $view->with('unseenGameEvents', $unseenGameEvents);

        });

        view()->composer('partials.main-footer', function (View $view) {
            $version = (Cache::has('version-html') ? Cache::get('version-html') : 'unknown');
            $view->with('version', $version);
        });

        view()->composer('partials.notification-nav', function (View $view) {
            $view->with('notificationHelper', app(NotificationHelper::class));
        });

        // todo: do we need this here in this class?
        view()->composer('partials.resources-overview', function (View $view) {
            $view->with('networthCalculator', app(NetworthCalculator::class));
        });
    }
}

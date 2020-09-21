<?php

namespace OpenDominion\Providers;

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

            /** @var Dominion $selectedDominion */
            $selectedDominion = $selectorService->getUserSelectedDominion();

            $councilLastRead = $selectedDominion->council_last_read;
            $councilUnreadCount = $selectedDominion->realm
                ->councilThreads()
                ->with(['posts' => function ($query) use ($councilLastRead) {
                    if ($councilLastRead !== null) {
                        $query->where('created_at', '>', $councilLastRead);
                    }
                }])
                ->get()
                ->map(static function (Council\Thread $thread) use ($councilLastRead) {
                    $unreadCount = $thread->posts->count();

                    if ($thread->created_at > $councilLastRead) {
                        $unreadCount++;
                    }

                    return $unreadCount;
                })
                ->sum();
            $view->with('councilUnreadCount', $councilUnreadCount);

            $forumLastRead = $selectedDominion->forum_last_read;
            $forumUnreadCount = $selectedDominion->round
                ->forumThreads()
                ->with(['posts' => function ($query) use ($forumLastRead) {
                    if ($forumLastRead !== null) {
                        $query->where('created_at', '>', $forumLastRead);
                    }
                }])
                ->get()
                ->map(static function (Forum\Thread $thread) use ($forumLastRead) {
                    $unreadCount = $thread->posts->count();

                    if ($thread->created_at > $forumLastRead) {
                        $unreadCount++;
                    }

                    return $unreadCount;
                })
                ->sum();
            $view->with('forumUnreadCount', $forumUnreadCount);

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

            $view->with('unseenWonders', 2);
            $view->with('unseenGameEvents', 14);

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

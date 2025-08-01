<?php

namespace OpenDominion\Providers;

use Auth;
use Cache;
use DB;
use Illuminate\Contracts\View\View;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\Bounty;
use OpenDominion\Models\MessageBoard;
use OpenDominion\Services\Dominion\ProtectionService;
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

            /** @var User $user */
            $user = Auth::getUser();

            $messageBoardLastRead = $user->message_board_last_read ?? $user->created_at;
            $messageBoardUnreadCount = MessageBoard\Thread::query()
                ->where('last_activity', '>', $messageBoardLastRead)
                ->withCount(['posts' => function ($query) use ($messageBoardLastRead) {
                    $query->where('created_at', '>', $messageBoardLastRead);
                }])
                ->get()
                ->map(function ($thread) use ($messageBoardLastRead) {
                    if ($thread->created_at > $messageBoardLastRead) {
                        $thread['posts_count'] += 1;
                    }
                    return $thread;
                })
                ->sum('posts_count');
            $view->with('messageBoardUnreadCount', $messageBoardUnreadCount);

            if (!$selectorService->hasUserSelectedDominion()) {
                return;
            }

            /** @var Dominion $selectedDominion */
            $selectedDominion = $selectorService->getUserSelectedDominion();

            $councilLastRead = $selectedDominion->council_last_read ?? $selectedDominion->round->created_at;
            $councilUnreadCount = $selectedDominion->realm->councilThreads()
                ->where('last_activity', '>', $councilLastRead)
                ->withCount(['posts' => function ($query) use ($councilLastRead) {
                    $query->where('created_at', '>', $councilLastRead);
                }])
                ->get()
                ->map(function ($thread) use ($councilLastRead) {
                    if ($thread->created_at > $councilLastRead) {
                        $thread['posts_count'] += 1;
                    }
                    return $thread;
                })
                ->sum('posts_count');
            $view->with('councilUnreadCount', $councilUnreadCount);

            $forumLastRead = $selectedDominion->forum_last_read ?? $selectedDominion->round->created_at;
            $forumUnreadCount = $selectedDominion->round->forumThreads()
                ->where('last_activity', '>', $forumLastRead)
                ->withCount(['posts' => function ($query) use ($forumLastRead) {
                    $query->where('created_at', '>', $forumLastRead);
                }])
                ->get()
                ->map(function ($thread) use ($forumLastRead) {
                    if ($thread->created_at > $forumLastRead) {
                        $thread['posts_count'] += 1;
                    }
                    return $thread;
                })
                ->sum('posts_count');
            $view->with('forumUnreadCount', $forumUnreadCount);

            $activeSelfSpells = $selectedDominion->spells->where('category', 'self')->count();
            $activeHostileSpells = $selectedDominion->spells->filter(function ($spell) {
                return $spell->isHarmful();
            })->count();
            $activeFriendlySpells = $selectedDominion->spells->filter(function ($spell) {
                return !$spell->isHarmful() && $spell->category !== 'self';
            })->count();
            $view->with('activeSelfSpells', $activeSelfSpells);
            $view->with('activeHostileSpells', $activeHostileSpells);
            $view->with('activeFriendlySpells', $activeFriendlySpells);

            // Show icon for techs
            $techCalculator = app(TechCalculator::class);
            $techCost = $techCalculator->getTechCost($selectedDominion);
            $unlockableTechCount = rfloor($selectedDominion->resource_tech / $techCost);
            $view->with('unlockableTechCount', $unlockableTechCount);

            // Show icon for heroes
            $heroCalculator = app(HeroCalculator::class);
            $unlockableHeroUpgradeCount = $heroCalculator->getUnlockableUpgradeCount($selectedDominion->hero);
            $view->with('unlockableHeroUpgradeCount', $unlockableHeroUpgradeCount);

            // Show barren land count
            $landCalculator = app(LandCalculator::class);
            $barrenLand = $landCalculator->getTotalBarrenLand($selectedDominion);
            $view->with('barrenLand', $barrenLand);

            $activeBounties = Bounty::active()
                ->where('source_realm_id', $selectedDominion->realm_id)
                ->where('source_dominion_id', '!=', $selectedDominion->id)
                ->count();
            $view->with('activeBounties', $activeBounties);

            $postedBounties = Bounty::active()
                ->where('source_realm_id', $selectedDominion->realm_id)
                ->where('source_dominion_id', '=', $selectedDominion->id)
                ->count();
            $view->with('postedBounties', $postedBounties);

            $activeRaids = $selectedDominion->round->raids()->active()->count();
            $view->with('activeRaids', $activeRaids);

            $unseenWonders = DB::table('round_wonders')
                ->where('round_id', $selectedDominion->round_id)
                ->where('created_at', '>', $selectedDominion->wonders_last_seen ?? $selectedDominion->round->created_at)
                ->count();
            $view->with('unseenWonders', $unseenWonders);

            $unseenGameEvents = DB::table('game_events')
                ->where('round_id', $selectedDominion->round_id)
                ->where('created_at', '>', $selectedDominion->town_crier_last_seen ?? $selectedDominion->round->created_at)
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
            $view->with('landCalculator', app(LandCalculator::class));
            $view->with('networthCalculator', app(NetworthCalculator::class));
            $view->with('techCalculator', app(TechCalculator::class));
        });

        view()->composer('partials.protection-indicator', function (View $view) {
            $view->with('protectionService', app(ProtectionService::class));
        });

        view()->composer('partials.styles', function (View $view) {
            $version = (Cache::has('version') ? Cache::get('version') : 'unknown');
            $view->with('version', $version);
        });
    }
}

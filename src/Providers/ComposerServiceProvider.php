<?php

namespace OpenDominion\Providers;

use Auth;
use Cache;
use DB;
use Illuminate\Contracts\View\View;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Helpers\MiscHelper;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\Bounty;
use OpenDominion\Models\MessageBoard;
use OpenDominion\Models\Valuable;
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
            if ($user->getSetting('message_board_announcements_only')) {
                $messageBoardUnreadCount = MessageBoard\Thread::where('created_at', '>', $messageBoardLastRead)
                    ->whereHas('category', function ($q) {
                        $q->where('slug', 'announcements');
                    })
                    ->count();
            } else {
                $messageBoardUnreadCount = MessageBoard\Thread::where('last_activity', '>', $messageBoardLastRead)->count();
            }
            $view->with('messageBoardUnreadCount', $messageBoardUnreadCount);

            if (!$selectorService->hasUserSelectedDominion()) {
                return;
            }

            /** @var Dominion $selectedDominion */
            $selectedDominion = $selectorService->getUserSelectedDominion();

            $councilLastRead = $selectedDominion->council_last_read ?? $selectedDominion->round->created_at;
            $councilUnreadCount = $selectedDominion->realm->councilThreads()
                ->where('last_activity', '>', $councilLastRead)
                ->count();
            $view->with('councilUnreadCount', $councilUnreadCount);

            $forumLastRead = $selectedDominion->forum_last_read ?? $selectedDominion->round->created_at;
            $forumUnreadCount = $selectedDominion->round->forumThreads()
                ->where('last_activity', '>', $forumLastRead)
                ->count();
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

            // Show indicator for temporary tech selection (Planar Gates)
            $needsTemporaryTech = false;
            if ($selectedDominion->getWonderPerkValue('temporary_tech') > 0) {
                $hasTemporaryTech = $selectedDominion->techs->contains(function ($tech) {
                    return $tech->pivot->source_id !== null;
                });
                $needsTemporaryTech = !$hasTemporaryTech;
            }
            $view->with('needsTemporaryTech', $needsTemporaryTech);

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

            // Valuables badges: discovered/transferred (actionable) + stolen (sellable).
            // Excludes valuables the player has already listed for transfer themselves —
            // they're awaiting a realmmate purchase, not waiting on the owner to act.
            $valuablesDiscoveredCount = Valuable::query()
                ->where('source_dominion_id', $selectedDominion->id)
                ->where('is_listed', false)
                ->whereIn('status', [
                    Valuable::STATUS_DISCOVERED,
                    Valuable::STATUS_LISTED_FOR_TRANSFER,
                    Valuable::STATUS_TRANSFERRED,
                ])
                ->count();
            $valuablesStolenCount = Valuable::query()
                ->where('source_dominion_id', $selectedDominion->id)
                ->where('status', Valuable::STATUS_STOLEN)
                ->count();
            $intelForSaleCount = Valuable::query()
                ->where('round_id', $selectedDominion->round_id)
                ->where('is_listed', true)
                ->where('source_dominion_id', '!=', $selectedDominion->id)
                ->whereHas('sourceDominion', function ($q) use ($selectedDominion) {
                    $q->where('realm_id', $selectedDominion->realm_id);
                })
                ->count();
            $view->with('valuablesDiscoveredCount', $valuablesDiscoveredCount);
            $view->with('valuablesStolenCount', $valuablesStolenCount);
            $view->with('intelForSaleCount', $intelForSaleCount);
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
            $view->with('militaryCalculator', app(MilitaryCalculator::class));
            $view->with('miscHelper', app(MiscHelper::class));
            $view->with('populationCalculator', app(PopulationCalculator::class));
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

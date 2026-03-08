<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ url('') }}" class="brand-link">
            <span class="brand-text">Open<b>Dominion</b></span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        @php
            $hiddenLinks = $selectedDominion->settings['hidden_links'] ?? [];
        @endphp

        @if (isset($selectedDominion))
            <div class="sidebar-user d-flex align-items-center gap-2 py-3 px-3">
                <img src="{{ Auth::user()->getAvatarUrl() }}" class="img-circle" style="width:35px;height:35px;" alt="{{ Auth::user()->display_name }}">
                <div>
                    <span class="d-block fw-bold small">{{ $selectedDominion->name }}</span>
                    <a href="{{ route('dominion.realm') }}" class="small">{{ $selectedDominion->realm->name }} (#{{ $selectedDominion->realm->number }})</a>
                </div>
            </div>
        @endif

        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                @if (isset($selectedDominion))

                    <li class="nav-header">GENERAL</li>
                    <li class="nav-item {{ Route::is('dominion.status') ? 'active' : null }}">
                        <a href="{{ route('dominion.status') }}" class="nav-link {{ Route::is('dominion.status') ? 'active' : null }}">
                            <i class="nav-icon fa fa-bar-chart fa-fw"></i> <p>Status</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.advisors.*') ? 'active' : null }}">
                        <a href="{{ route('dominion.advisors') }}" class="nav-link {{ Route::is('dominion.advisors.*') ? 'active' : null }}">
                            <i class="nav-icon fa fa-question-circle fa-fw"></i> <p>Advisors</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.bonuses.actions') ? 'active' : null }}">
                        <a href="{{ route('dominion.bonuses.actions') }}" class="nav-link {{ Route::is('dominion.bonuses.actions') ? 'active' : null }}">
                            <i class="nav-icon ra ra-robot-arm ra-fw"></i> <p>Automation</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.bonuses') ? 'active' : null }}">
                        <a href="{{ route('dominion.bonuses') }}" class="nav-link {{ Route::is('dominion.bonuses') ? 'active' : null }}">
                            <i class="nav-icon fa fa-plus fa-fw"></i>
                            <p>Daily Bonus
                                @if (!$selectedDominion->daily_platinum)
                                    <span class="badge text-bg-primary ms-auto">P</span>
                                @endif
                                @if (!$selectedDominion->daily_land)
                                    <span class="badge text-bg-primary ms-auto">L</span>
                                @endif
                            </p>
                        </a>
                        @if (!$selectedDominion->isBuildingPhase())
                            <a href="{{ route('dominion.bonuses') }}" class="nav-link" style="padding-top: 0px;">
                                <i class="nav-icon fa fa-fw"></i>
                                @php
                                    if ($selectedDominion->protection_ticks_remaining > 0) {
                                        if ($selectedDominion->protection_type !== 'quick') {
                                            $hoursUntilReset = $selectedDominion->protection_ticks_remaining % 24;
                                        } else {
                                            $hoursUntilReset = $selectedDominion->protection_ticks_remaining;
                                        }
                                    } elseif ($selectedDominion->round->start_date->addHours(24) > now()) {
                                        $hoursUntilReset = $selectedDominion->round->start_date->addHours(24)->diffInHours(now()->startOfHour());
                                    } else {
                                        $hoursUntilReset = $selectedDominion->round->start_date->hour - now()->hour;
                                    }
                                    if ($hoursUntilReset < 1) {
                                        $hoursUntilReset = 24 + $hoursUntilReset;
                                    }
                                @endphp
                                <p><small>
                                    Resets in
                                    @if ($hoursUntilReset < 4)
                                        <strong class="text-orange">{{ $hoursUntilReset }}</strong>
                                    @else
                                        {{ $hoursUntilReset }}
                                    @endif
                                    {{ str_plural('tick', $hoursUntilReset) }}
                                </small></p>
                            </a>
                        @endif
                    </li>

                    <li class="nav-header">DOMINION</li>
                    <li class="nav-item {{ Route::is('dominion.explore') ? 'active' : null }} {{ in_array('explore_land', $hiddenLinks) ? 'd-none' : null }}">
                        <a href="{{ route('dominion.explore') }}" class="nav-link {{ Route::is('dominion.explore') ? 'active' : null }}">
                            <i class="nav-icon ra ra-telescope ra-fw"></i> <p>Explore Land</p>
                        </a>
                    </li>
                    @if ($selectedDominion->isBuildingPhase())
                        <li class="nav-item {{ Route::is('dominion.protection.buildings') ? 'active' : null }}">
                            <a href="{{ route('dominion.protection.buildings') }}" class="nav-link {{ Route::is('dominion.protection.buildings') ? 'active' : null }}">
                                <i class="nav-icon fa fa-home fa-fw"></i>
                                <p>Construct Buildings
                                    @if ($barrenLand > 0)
                                        <span class="badge text-bg-primary ms-auto">{{ $barrenLand }}</span>
                                    @endif
                                </p>
                            </a>
                        </li>
                    @else
                        <li class="nav-item {{ Route::is('dominion.construct') ? 'active' : null }}">
                            <a href="{{ route('dominion.construct') }}" class="nav-link {{ Route::is('dominion.construct') ? 'active' : null }}">
                                <i class="nav-icon fa fa-home fa-fw"></i>
                                <p>Construct Buildings
                                    @if ($barrenLand > 0)
                                        <span class="badge text-bg-primary ms-auto">{{ $barrenLand }}</span>
                                    @endif
                                </p>
                            </a>
                        </li>
                    @endif
                    <li class="nav-item {{ Route::is('dominion.rezone') ? 'active' : null }}">
                        <a href="{{ route('dominion.rezone') }}" class="nav-link {{ Route::is('dominion.rezone') ? 'active' : null }}">
                            <i class="nav-icon fa fa-refresh fa-fw"></i> <p>Re-zone Land</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.improvements') ? 'active' : null }}">
                        <a href="{{ route('dominion.improvements') }}" class="nav-link {{ Route::is('dominion.improvements') ? 'active' : null }}">
                            <i class="nav-icon fa fa-arrow-up fa-fw"></i> <p>Improvements</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.bank') ? 'active' : null }}">
                        <a href="{{ route('dominion.bank') }}" class="nav-link {{ Route::is('dominion.bank') ? 'active' : null }}">
                            <i class="nav-icon fa fa-money fa-fw"></i> <p>National Bank</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.techs') ? 'active' : null }}">
                        <a href="{{ route('dominion.techs') }}" class="nav-link {{ Route::is('dominion.techs') ? 'active' : null }}">
                            <i class="nav-icon fa fa-flask fa-fw"></i>
                            <p>Technology
                                @if ($unlockableTechCount > 0)
                                    <span class="badge text-bg-primary ms-auto">{{ $unlockableTechCount }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.heroes') ? 'active' : null }}">
                        <a href="{{ route('dominion.heroes') }}" class="nav-link {{ Route::is('dominion.heroes') ? 'active' : null }}">
                            <i class="nav-icon ra ra-knight-helmet ra-fw"></i>
                            <p>Heroes
                                @if (!$selectedDominion->hero || $unlockableHeroUpgradeCount > 0)
                                    <span class="badge text-bg-primary ms-auto">{{ $unlockableHeroUpgradeCount ?: 1 }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                    @if ($selectedDominion->hero)
                        <li class="nav-item {{ Route::is('dominion.heroes.battles') ? 'active' : null }} {{ in_array('hero_battles', $hiddenLinks) ? 'd-none' : null }}">
                            <a href="{{ route('dominion.heroes.battles') }}" class="nav-link {{ Route::is('dominion.heroes.battles') ? 'active' : null }}">
                                <i class="nav-icon ra ra-axe ra-fw"></i>
                                <p>Hero Battles
                                    @if ($selectedDominion->hero->combatActionRequired() > 0)
                                        <span class="badge text-bg-primary ms-auto">{{ $selectedDominion->hero->combatActionRequired() }}</span>
                                    @endif
                                </p>
                            </a>
                        </li>
                    @endif
                    @if ($selectedDominion->round->tournaments()->count() > 0)
                        <li class="nav-item {{ Route::is('dominion.heroes.tournaments') ? 'active' : null }} {{ in_array('hero_tournament', $hiddenLinks) ? 'd-none' : null }}">
                            <a href="{{ route('dominion.heroes.tournaments') }}" class="nav-link {{ Route::is('dominion.heroes.tournaments') ? 'active' : null }}">
                                <i class="nav-icon fa fa-trophy fa-fw"></i>
                                <p>Hero Tournament
                                    @if ($selectedDominion->round->tournaments()->where('start_date', '>', now())->count() > 0)
                                        <span class="badge text-bg-primary ms-auto">R</span>
                                    @endif
                                </p>
                            </a>
                        </li>
                    @endif
                    <li class="nav-item {{ Route::is('dominion.raids*') ? 'active' : null }}">
                        <a href="{{ route('dominion.raids') }}" class="nav-link {{ Route::is('dominion.raids*') ? 'active' : null }}">
                            <i class="nav-icon ra ra-castle-flag ra-fw"></i>
                            <p>Raids
                                @if ($activeRaids > 0)
                                    <span class="badge text-bg-primary ms-auto">{{ $activeRaids }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.journal') ? 'active' : null }} {{ in_array('journal', $hiddenLinks) ? 'd-none' : null }}">
                        <a href="{{ route('dominion.journal') }}" class="nav-link {{ Route::is('dominion.journal') ? 'active' : null }}">
                            <i class="nav-icon ra ra-scroll-quill ra-fw"></i> <p>Journal</p>
                        </a>
                    </li>

                    <li class="nav-header">OPERATIONS</li>
                    <li class="nav-item {{ Route::is('dominion.military') ? 'active' : null }}">
                        <a href="{{ route('dominion.military') }}" class="nav-link {{ Route::is('dominion.military') ? 'active' : null }}">
                            <i class="nav-icon ra ra-sword ra-fw"></i> <p>Military</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.invade') ? 'active' : null }} {{ in_array('invade', $hiddenLinks) ? 'd-none' : null }}">
                        <a href="{{ route('dominion.invade') }}" class="nav-link {{ Route::is('dominion.invade') ? 'active' : null }}">
                            <i class="nav-icon ra ra-crossed-swords ra-fw"></i> <p>Invade</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.magic') ? 'active' : null }}">
                        <a href="{{ route('dominion.magic') }}" class="nav-link {{ Route::is('dominion.magic') ? 'active' : null }}">
                            <i class="nav-icon ra ra-fairy-wand ra-fw"></i>
                            <p>Magic
                                @if ($activeSelfSpells > 0)
                                    <span class="badge bg-primary ms-auto">{{ $activeSelfSpells }}</span>
                                @endif
                                @if ($activeHostileSpells > 0)
                                    <span class="badge bg-danger ms-auto">{{ $activeHostileSpells }}</span>
                                @endif
                                @if ($activeFriendlySpells > 0)
                                    <span class="badge bg-success ms-auto">{{ $activeFriendlySpells }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.espionage') ? 'active' : null }}">
                        <a href="{{ route('dominion.espionage') }}" class="nav-link {{ Route::is('dominion.espionage') ? 'active' : null }}">
                            <i class="nav-icon fa fa-user-secret fa-fw"></i> <p>Espionage</p>
                        </a>
                    </li>
                    @if ($selectedDominion->black_guard_active_at !== null)
                        <li class="nav-item {{ Route::is('dominion.black-guard') ? 'active' : null }}">
                            <a href="{{ route('dominion.black-guard') }}" class="nav-link {{ Route::is('dominion.black-guard') ? 'active' : null }}">
                                <i class="nav-icon ra ra-fire-shield ra-fw"></i> <p>Chaos League</p>
                            </a>
                        </li>
                    @endif
                    <li class="nav-item {{ Route::is('dominion.op-center*') ? 'active' : null }}">
                        <a href="{{ route('dominion.op-center') }}" class="nav-link {{ Route::is('dominion.op-center*') ? 'active' : null }}">
                            <i class="nav-icon ra ra-scroll-unfurled ra-fw"></i> <p>Op Center</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.bounty-board') ? 'active' : null }}">
                        <a href="{{ route('dominion.bounty-board') }}" class="nav-link {{ Route::is('dominion.bounty-board') ? 'active' : null }}">
                            <i class="nav-icon ra ra-hanging-sign ra-fw"></i>
                            <p>Bounty Board
                                @if ($activeBounties > 0)
                                    <span class="badge text-bg-primary ms-auto">{{ $activeBounties }}</span>
                                @endif
                                @if ($postedBounties > 0)
                                    <span class="badge bg-primary ms-auto">{{ $postedBounties }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.calculations') ? 'active' : null }} {{ in_array('calculators', $hiddenLinks) ? 'd-none' : null }}">
                        <a href="{{ route('dominion.calculations') }}" class="nav-link {{ Route::is('dominion.calculations') ? 'active' : null }}">
                            <i class="nav-icon fa fa-calculator fa-fw"></i> <p>Calculators</p>
                        </a>
                    </li>

                    <li class="nav-header">RELATIONS</li>
                    <li class="nav-item {{ Route::is('dominion.realm') ? 'active' : null }}">
                        <a href="{{ route('dominion.realm') }}" class="nav-link {{ Route::is('dominion.realm') ? 'active' : null }}">
                            <i class="nav-icon ra ra-circle-of-circles ra-fw"></i> <p>Realms</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.world') ? 'active' : null }} {{ in_array('world', $hiddenLinks) ? 'd-none' : null }}">
                        <a href="{{ route('dominion.world') }}" class="nav-link {{ Route::is('dominion.world') ? 'active' : null }}">
                            <i class="nav-icon fa fa-globe fa-fw"></i> <p>The World</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.search') ? 'active' : null }}">
                        <a href="{{ route('dominion.search') }}" class="nav-link {{ Route::is('dominion.search') ? 'active' : null }}">
                            <i class="nav-icon fa fa-search fa-fw"></i> <p>Search</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.council*') ? 'active' : null }} {{ in_array('council', $hiddenLinks) ? 'd-none' : null }}">
                        <a href="{{ route('dominion.council') }}" class="nav-link {{ Route::is('dominion.council*') ? 'active' : null }}">
                            <i class="nav-icon fa fa-group fa-fw"></i>
                            <p>The Council
                                @if ($councilUnreadCount > 0 && !Route::is('dominion.council'))
                                    <span class="badge bg-success ms-auto">{{ $councilUnreadCount }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.government') ? 'active' : null }}">
                        <a href="{{ route('dominion.government') }}" class="nav-link {{ Route::is('dominion.government') ? 'active' : null }}">
                            <i class="nav-icon fa fa-university fa-fw"></i> <p>Government</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.wonders') ? 'active' : null }}">
                        <a href="{{ route('dominion.wonders') }}" class="nav-link {{ Route::is('dominion.wonders') ? 'active' : null }}">
                            <i class="nav-icon ra ra-pyramids ra-fw ra-lg"></i>
                            <p>Wonders
                                @if ($unseenWonders > 0 && !Route::is('dominion.wonders'))
                                    <span class="badge bg-success ms-auto">{{ $unseenWonders }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.rankings') ? 'active' : null }} {{ in_array('rankings', $hiddenLinks) ? 'd-none' : null }}">
                        <a href="{{ route('dominion.rankings') }}" class="nav-link {{ Route::is('dominion.rankings') ? 'active' : null }}">
                            <i class="nav-icon fa fa-trophy fa-fw"></i> <p>Rankings</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.forum*') ? 'active' : null }} {{ in_array('forum', $hiddenLinks) ? 'd-none' : null }}">
                        <a href="{{ route('dominion.forum') }}" class="nav-link {{ Route::is('dominion.forum*') ? 'active' : null }}">
                            <i class="nav-icon fa fa-comments fa-fw"></i>
                            <p>Round Forum
                                @if ($forumUnreadCount > 0 && !Route::is('dominion.forum'))
                                    <span class="badge bg-success ms-auto">{{ $forumUnreadCount }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('dominion.town-crier') ? 'active' : null }}">
                        <a href="{{ route('dominion.town-crier') }}" class="nav-link {{ Route::is('dominion.town-crier') ? 'active' : null }}">
                            <i class="nav-icon fa fa-newspaper-o fa-fw"></i>
                            <p>Town Crier
                                @if ($unseenGameEvents > 0 && !Route::is('dominion.town-crier'))
                                    <span class="badge bg-success ms-auto">{{ $unseenGameEvents }}</span>
                                @endif
                            </p>
                        </a>
                    </li>

                    <li class="nav-header">USER</li>
                    <li class="nav-item {{ Route::is('message-board*') ? 'active' : null }}">
                        <a href="{{ route('message-board') }}" class="nav-link {{ Route::is('message-board*') ? 'active' : null }}">
                            <i class="nav-icon ra ra-wooden-sign ra-fw"></i>
                            <p>Message Board
                                @if ($messageBoardUnreadCount > 0 && !Route::is('dominion.message-board'))
                                    <span class="badge bg-success ms-auto">{{ $messageBoardUnreadCount }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                    @if (app()->environment() !== 'production')
                        <li class="nav-item {{ Request::is('dominion/debug') ? 'active' : null }}">
                            <a href="{{ url('dominion/debug') }}" class="nav-link {{ Request::is('dominion/debug') ? 'active' : null }}">
                                <i class="nav-icon ra ra-dragon ra-fw"></i> <p>Debug Page</p>
                            </a>
                        </li>
                    @endif

                @else

                    <li class="nav-header">USER</li>
                    <li class="nav-item {{ Route::is('dashboard') ? 'active' : null }}">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ Route::is('dashboard') ? 'active' : null }}">
                            <i class="nav-icon ra ra-capitol ra-fw"></i> <p>Select Dominion</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('message-board*') ? 'active' : null }}">
                        <a href="{{ route('message-board') }}" class="nav-link {{ Route::is('message-board*') ? 'active' : null }}">
                            <i class="nav-icon ra ra-wooden-sign ra-fw"></i>
                            <p>Message Board
                                @if ($messageBoardUnreadCount > 0 && !Route::is('dominion.message-board'))
                                    <span class="badge bg-success ms-auto">{{ $messageBoardUnreadCount }}</span>
                                @endif
                            </p>
                        </a>
                    </li>

                @endif
            </ul>

        </nav>

    </div>
</aside>

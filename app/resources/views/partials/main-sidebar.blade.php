<aside class="main-sidebar">
    <section class="sidebar">

        @if (isset($selectedDominion))
            <div class="user-panel">
                <div class="pull-left image">
                    <img src="{{ Auth::user()->getAvatarUrl() }}" class="img-circle" alt="{{ Auth::user()->display_name }}">
                </div>
                <div class="pull-left info">
                    <p>{{ $selectedDominion->name }}</p>
                    <a href="{{ route('dominion.realm') }}">{{ $selectedDominion->realm->name }} (#{{ $selectedDominion->realm->number }})</a>
                </div>
            </div>
        @endif

        <ul class="sidebar-menu" data-widget="tree">
            @if (isset($selectedDominion))

                <li class="header">GENERAL</li>
                <li class="{{ Route::is('dominion.status') ? 'active' : null }}"><a href="{{ route('dominion.status') }}"><i class="fa fa-bar-chart fa-fw"></i> <span>Status</span></a></li>
                <li class="{{ Route::is('dominion.advisors.*') ? 'active' : null }}"><a href="{{ route('dominion.advisors') }}"><i class="fa fa-question-circle fa-fw"></i> <span>Advisors</span></a></li>
                <li class="{{ Route::is('dominion.bonuses.actions') ? 'active' : null }}"><a href="{{ route('dominion.bonuses.actions') }}"><i class="ra ra-robot-arm ra-fw"></i> <span>Automation</span></a></li>
                <li class="{{ Route::is('dominion.bonuses') ? 'active' : null }}">
                    <a href="{{ route('dominion.bonuses') }}">
                        <i class="fa fa-plus fa-fw"></i>
                        <span>Daily Bonus</span>
                        <span class="pull-right-container">
                            @if (!$selectedDominion->daily_platinum)
                                <span class="label label-primary pull-right">P</span>
                            @endif
                            @if (!$selectedDominion->daily_land)
                                <span class="label label-primary pull-right">L</span>
                            @endif
                        </span>
                    </a>
                    @if (!$selectedDominion->isBuildingPhase())
                        <a href="{{ route('dominion.bonuses') }}" style="padding-top: 0px;">
                            <i class="fa fa-fw"></i>
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
                            <span class="small">
                                Resets in
                                @if ($hoursUntilReset < 4)
                                    <strong class="text-orange">{{ $hoursUntilReset }}</strong>
                                @else
                                    {{ $hoursUntilReset }}
                                @endif
                                {{ str_plural('tick', $hoursUntilReset) }}
                            </span>
                        </a>
                    @endif
                </li>

                <li class="header">DOMINION</li>
                <li class="{{ Route::is('dominion.explore') ? 'active' : null }}"><a href="{{ route('dominion.explore') }}"><i class="ra ra-telescope ra-fw"></i> <span>Explore Land</span></a></li>
                @if ($selectedDominion->isBuildingPhase())
                    <li class="{{ Route::is('dominion.protection.buildings') ? 'active' : null }}">
                        <a href="{{ route('dominion.protection.buildings') }}">
                            <i class="fa fa-home fa-fw"></i> <span>Construct Buildings</span>
                            @if ($barrenLand > 0)
                                <span class="pull-right-container">
                                    <span class="label label-primary pull-right">
                                        {{ $barrenLand }}
                                    </span>
                                </span>
                            @endif
                        </a>
                    </li>
                @else
                    <li class="{{ Route::is('dominion.construct') ? 'active' : null }}">
                        <a href="{{ route('dominion.construct') }}">
                            <i class="fa fa-home fa-fw"></i> <span>Construct Buildings</span>
                            @if ($barrenLand > 0)
                                <span class="pull-right-container">
                                    <span class="label label-primary pull-right">
                                        {{ $barrenLand }}
                                    </span>
                                </span>
                            @endif
                        </a>
                    </li>
                @endif
                <li class="{{ Route::is('dominion.rezone') ? 'active' : null }}"><a href="{{ route('dominion.rezone') }}"><i class="fa fa-refresh fa-fw"></i> <span>Re-zone Land</span></a></li>
                <li class="{{ Route::is('dominion.improvements') ? 'active' : null }}"><a href="{{ route('dominion.improvements') }}"><i class="fa fa-arrow-up fa-fw"></i> <span>Improvements</span></a></li>
                <li class="{{ Route::is('dominion.bank') ? 'active' : null }}"><a href="{{ route('dominion.bank') }}"><i class="fa fa-money fa-fw"></i> <span>National Bank</span></a></li>
                <li class="{{ Route::is('dominion.techs') ? 'active' : null }}">
                    <a href="{{ route('dominion.techs') }}">
                        <i class="fa fa-flask fa-fw"></i> <span>Technology</span>
                        @if ($unlockableTechCount > 0)
                            <span class="pull-right-container">
                                <span class="label label-primary pull-right">
                                    {{ $unlockableTechCount }}
                                </span>
                            </span>
                        @endif
                    </a>
                </li>
                <li class="{{ Route::is('dominion.heroes') ? 'active' : null }}">
                    <a href="{{ route('dominion.heroes') }}">
                        <i class="ra ra-knight-helmet ra-fw"></i> <span>Heroes</span>
                        @if (!$selectedDominion->hero || $unlockableHeroUpgradeCount > 0)
                            <span class="pull-right-container">
                                <span class="label label-primary pull-right">
                                    {{ $unlockableHeroUpgradeCount ?: 1 }}
                                </span>
                            </span>
                        @endif
                    </a>
                </li>
                @if ($selectedDominion->hero)
                    <li class="{{ Route::is('dominion.heroes.battles') ? 'active' : null }}">
                        <a href="{{ route('dominion.heroes.battles') }}">
                            <i class="ra ra-axe ra-fw"></i> <span>Hero Battles</span>
                            @if ($selectedDominion->hero->combatActionRequired() > 0)
                                <span class="pull-right-container">
                                    <span class="label label-primary pull-right">
                                        {{ $selectedDominion->hero->combatActionRequired() }}
                                    </span>
                                </span>
                            @endif
                        </a>
                    </li>
                @endif
                @if ($selectedDominion->round->tournaments()->count() > 0)
                    <li class="{{ Route::is('dominion.heroes.tournaments') ? 'active' : null }}">
                        <a href="{{ route('dominion.heroes.tournaments') }}">
                            <i class="fa fa-trophy fa-fw"></i> <span>Hero Tournament</span>
                            @if ($selectedDominion->round->tournaments()->where('start_date', '>', now())->count() > 0)
                                <span class="pull-right-container">
                                    <span class="label label-primary pull-right">
                                        R
                                    </span>
                                </span>
                            @endif
                        </a>
                    </li>
                @endif
                <li class="{{ Route::is('dominion.journal') ? 'active' : null }}"><a href="{{ route('dominion.journal') }}"><i class="ra ra-scroll-quill ra-fw"></i> <span>Journal</span></a></li>

                <li class="header">OPERATIONS</li>
                <li class="{{ Route::is('dominion.military') ? 'active' : null }}"><a href="{{ route('dominion.military') }}"><i class="ra ra-sword ra-fw"></i> <span>Military</span></a></li>
                <li class="{{ Route::is('dominion.invade') ? 'active' : null }}"><a href="{{ route('dominion.invade') }}"><i class="ra ra-crossed-swords ra-fw"></i> <span>Invade</span></a></li>
                <li class="{{ Route::is('dominion.magic') ? 'active' : null }}">
                    <a href="{{ route('dominion.magic') }}">
                        <i class="ra ra-fairy-wand ra-fw"></i> <span>Magic</span>
                        @if ($activeSelfSpells > 0 || $activeHostileSpells > 0 || $activeFriendlySpells > 0)
                            <span class="pull-right-container">
                                @if ($activeSelfSpells > 0)
                                    <small class="label pull-right bg-blue">
                                        {{ $activeSelfSpells }}
                                    </small>
                                @endif
                                @if ($activeHostileSpells > 0)
                                    <small class="label pull-right bg-red">
                                        {{ $activeHostileSpells }}
                                    </small>
                                @endif
                                @if ($activeFriendlySpells > 0)
                                    <small class="label pull-right bg-green">
                                        {{ $activeFriendlySpells }}
                                    </small>
                                @endif
                            </span>
                        @endif

                    </a>
                </li>
                <li class="{{ Route::is('dominion.espionage') ? 'active' : null }}"><a href="{{ route('dominion.espionage') }}"><i class="fa fa-user-secret fa-fw"></i> <span>Espionage</span></a></li>
                @if ($selectedDominion->black_guard_active_at !== null)
                    <li class="{{ Route::is('dominion.black-guard') ? 'active' : null }}"><a href="{{ route('dominion.black-guard') }}"><i class="ra ra-fire-shield ra-fw"></i> <span>Chaos League</span></a></li>
                @endif
                <li class="{{ Route::is('dominion.op-center*') ? 'active' : null }}"><a href="{{ route('dominion.op-center') }}"><i class="ra ra-scroll-unfurled ra-fw"></i> <span>Op Center</span></a></li>
                <li class="{{ Route::is('dominion.bounty-board') ? 'active' : null }}">
                    <a href="{{ route('dominion.bounty-board') }}">
                        <i class="ra ra-hanging-sign ra-fw"></i> <span>Bounty Board</span>
                        @if ($activeBounties > 0 || $postedBounties > 0)
                            <span class="pull-right-container">
                                @if ($activeBounties > 0)
                                    <small class="label label-primary pull-right">
                                        {{ $activeBounties }}
                                    </small>
                                @endif
                                @if ($postedBounties > 0)
                                    <small class="label pull-right bg-blue">
                                        {{ $postedBounties }}
                                    </small>
                                @endif
                            </span>
                        @endif
                    </a>
                </li>
                <li class="{{ Route::is('dominion.calculations') ? 'active' : null }}"><a href="{{ route('dominion.calculations') }}"><i class="fa fa-calculator fa-fw"></i> <span>Calculators</span></a></li>

                <li class="header">RELATIONS</li>
                <li class="{{ Route::is('dominion.realm') ? 'active' : null }}"><a href="{{ route('dominion.realm') }}"><i class="ra ra-circle-of-circles ra-fw"></i> <span>Realms</span></a></li>
                <li class="{{ Route::is('dominion.world') ? 'active' : null }}"><a href="{{ route('dominion.world') }}"><i class="fa fa-globe fa-fw"></i> <span>The World</span></a></li>
                <li class="{{ Route::is('dominion.search') ? 'active' : null }}"><a href="{{ route('dominion.search') }}"><i class="fa fa-search fa-fw"></i> <span>Search</span></a></li>
                <li class="{{ Route::is('dominion.council*') ? 'active' : null }}">
                    <a href="{{ route('dominion.council') }}">
                        <i class="fa fa-group fa-fw"></i> <span>The Council</span>
                        @if ($councilUnreadCount > 0 && !Route::is('dominion.council'))
                            <span class="pull-right-container">
                                <small class="label pull-right bg-green">{{ $councilUnreadCount }}</small>
                            </span>
                        @endif
                    </a>
                </li>
                <li class="{{ Route::is('dominion.government') ? 'active' : null }}"><a href="{{ route('dominion.government') }}"><i class="fa fa-university fa-fw"></i> <span>Government</span></a></li>
                <li class="{{ Route::is('dominion.wonders') ? 'active' : null }}">
                    <a href="{{ route('dominion.wonders') }}">
                        <i class="ra ra-pyramids ra-fw ra-lg"></i> <span>Wonders</span>
                        @if ($unseenWonders > 0 && !Route::is('dominion.wonders'))
                            <span class="pull-right-container">
                                <span class="label pull-right bg-green">{{ $unseenWonders }}</span>
                            </span>
                        @endif
                    </a>
                </li>
                <li class="{{ Route::is('dominion.rankings') ? 'active' : null }}"><a href="{{ route('dominion.rankings') }}"><i class="fa fa-trophy fa-fw"></i> <span>Rankings</span></a></li>
                <li class="{{ Route::is('dominion.forum*') ? 'active' : null }}">
                    <a href="{{ route('dominion.forum') }}">
                        <i class="fa fa-comments fa-fw"></i> <span>Round Forum</span>
                        @if ($forumUnreadCount > 0 && !Route::is('dominion.forum'))
                            <span class="pull-right-container">
                                <small class="label pull-right bg-green">{{ $forumUnreadCount }}</small>
                            </span>
                        @endif
                    </a>
                </li>
                <li class="{{ Route::is('dominion.town-crier') ? 'active' : null }}">
                    <a href="{{ route('dominion.town-crier') }}">
                        <i class="fa fa-newspaper-o fa-fw"></i> <span>Town Crier</span>
                        @if ($unseenGameEvents > 0 && !Route::is('dominion.town-crier'))
                            <span class="pull-right-container">
                                <span class="label pull-right bg-green">{{ $unseenGameEvents }}</span>
                            </span>
                        @endif
                    </a>
                </li>

                <li class="header">USER</li>
                <li class="{{ Route::is('message-board*') ? 'active' : null }}">
                    <a href="{{ route('message-board') }}">
                        <i class="ra ra-wooden-sign ra-fw"></i> <span>Message Board</span>
                        @if ($messageBoardUnreadCount > 0 && !Route::is('dominion.message-board'))
                            <span class="pull-right-container">
                                <small class="label pull-right bg-green">{{ $messageBoardUnreadCount }}</small>
                            </span>
                        @endif
                    </a>
                </li>
                @if (app()->environment() !== 'production')
                    <li class="{{ Request::is('dominion/debug') ? 'active' : null }}"><a href="{{ url('dominion/debug') }}"><i class="ra ra-dragon ra-fw"></i> <span>Debug Page</span></a></li>
                @endif

            @else

                <li class="header">USER</li>
                <li class="{{ Route::is('dashboard') ? 'active' : null }}"><a href="{{ route('dashboard') }}"><i class="ra ra-capitol ra-fw"></i> <span>Select Dominion</span></a></li>
                <li class="{{ Route::is('message-board*') ? 'active' : null }}">
                    <a href="{{ route('message-board') }}">
                        <i class="ra ra-wooden-sign ra-fw"></i> <span>Message Board</span>
                        @if ($messageBoardUnreadCount > 0 && !Route::is('dominion.message-board'))
                            <span class="pull-right-container">
                                <small class="label pull-right bg-green">{{ $messageBoardUnreadCount }}</small>
                            </span>
                        @endif
                    </a>
                </li>

            @endif
        </ul>

    </section>
</aside>

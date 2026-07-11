@if (isset($selectedDominion))
@php
    $navLinks = [
        'advisors'     => ['icon' => 'ra ra-classical-knowledge', 'route' => 'dominion.advisors',   'active' => 'dominion.advisors*',            'stage' => 1],
        'explore'      => ['icon' => 'ra ra-telescope',           'route' => 'dominion.explore',     'active' => 'dominion.explore',              'stage' => 3],
        'construct'    => ['icon' => 'fa fa-home',                'route' => null,                   'active' => null,                            'stage' => 4],
        'improvements' => ['icon' => 'ra ra-castle',              'route' => 'dominion.improvements','active' => 'dominion.improvements',         'stage' => 5],
        'military'     => ['icon' => 'ra ra-sword',               'route' => 'dominion.military',    'active' => 'dominion.military',             'stage' => 5],
        'town_crier'   => ['icon' => 'fa fa-newspaper-o',         'route' => 'dominion.town-crier',  'active' => 'dominion.town-crier',           'stage' => 8],
        'bounty_board' => ['icon' => 'ra ra-hanging-sign',        'route' => 'dominion.bounty-board','active' => 'dominion.bounty-board',          'stage' => 6],
        'magic'        => ['icon' => 'ra ra-fairy-wand',          'route' => 'dominion.magic',       'active' => 'dominion.magic',                'stage' => 6],
        'espionage'    => ['icon' => 'fa fa-user-secret',         'route' => 'dominion.espionage',   'active' => 'dominion.espionage',            'stage' => 6],
        'bank'         => ['icon' => 'fa fa-money',               'route' => 'dominion.bank',        'active' => 'dominion.bank',                 'stage' => 5],
        'realm'        => ['icon' => 'ra ra-circle-of-circles',   'route' => 'dominion.realm',       'active' => 'dominion.realm',                'stage' => 7],
        'search'       => ['icon' => 'fa fa-search',              'route' => 'dominion.search',      'active' => 'dominion.search',               'stage' => 8],
        'calculate'    => ['icon' => 'fa fa-calculator',          'route' => null,                   'active' => 'dominion.calculations.military','stage' => 6],
        'sidebar'      => ['icon' => 'fa fa-bars',                'route' => null,                   'active' => null,                            'stage' => 0],
    ];

    $defaults = ['advisors', 'explore', 'construct', 'improvements', 'military', 'town_crier', 'sidebar'];
    $slots = $selectedDominion->settings['bottom_nav'] ?? $defaults;
@endphp
<nav class="mobile-bottom-nav d-lg-none">
    @foreach ($slots as $key)
        @if ($key === '' || !isset($navLinks[$key]))
            {{-- empty slot --}}
        @elseif ($onboardingStage !== null && $onboardingStage < $navLinks[$key]['stage'])
            {{-- This destination has not been introduced yet. --}}
        @elseif ($key === 'sidebar')
            <a class="mobile-bottom-nav-item" href="#" data-lte-toggle="sidebar" role="button">
                <i class="{{ $navLinks[$key]['icon'] }}"></i>
            </a>
        @elseif ($key === 'construct')
            <a class="mobile-bottom-nav-item {{ Route::is('dominion.construct') || Route::is('dominion.protection.buildings') ? 'active' : '' }}"
               href="{{ $selectedDominion->isBuildingPhase() ? route('dominion.protection.buildings') : route('dominion.construct') }}">
                <i class="{{ $navLinks[$key]['icon'] }}"></i>
            </a>
        @elseif ($key === 'calculate')
            <a class="mobile-bottom-nav-item {{ Route::is($navLinks[$key]['active']) ? 'active' : '' }}"
               href="{{ route('dominion.calculations.military', ['dominion' => $selectedDominion->id]) }}">
                <i class="{{ $navLinks[$key]['icon'] }}"></i>
            </a>
        @else
            <a class="mobile-bottom-nav-item {{ Route::is($navLinks[$key]['active']) ? 'active' : '' }}"
               href="{{ route($navLinks[$key]['route']) }}">
                <i class="{{ $navLinks[$key]['icon'] }}"></i>
            </a>
        @endif
    @endforeach
</nav>
@endif

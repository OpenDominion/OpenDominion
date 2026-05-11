@if (isset($selectedDominion))
@php
    $navLinks = [
        'advisors'     => ['icon' => 'ra ra-classical-knowledge', 'route' => 'dominion.advisors',   'active' => 'dominion.advisors*'],
        'explore'      => ['icon' => 'ra ra-telescope',           'route' => 'dominion.explore',     'active' => 'dominion.explore'],
        'construct'    => ['icon' => 'fa fa-home',                'route' => null,                   'active' => null],
        'improvements' => ['icon' => 'ra ra-castle',              'route' => 'dominion.improvements','active' => 'dominion.improvements'],
        'military'     => ['icon' => 'ra ra-sword',               'route' => 'dominion.military',    'active' => 'dominion.military'],
        'town_crier'   => ['icon' => 'fa fa-newspaper-o',         'route' => 'dominion.town-crier',  'active' => 'dominion.town-crier'],
        'bounty_board' => ['icon' => 'ra ra-hanging-sign',        'route' => 'dominion.bounty-board','active' => 'dominion.bounty-board'],
        'magic'        => ['icon' => 'ra ra-fairy-wand',          'route' => 'dominion.magic',       'active' => 'dominion.magic'],
        'espionage'    => ['icon' => 'fa fa-user-secret',         'route' => 'dominion.espionage',   'active' => 'dominion.espionage'],
        'bank'         => ['icon' => 'fa fa-money',               'route' => 'dominion.bank',        'active' => 'dominion.bank'],
        'sidebar'      => ['icon' => 'fa fa-bars',                'route' => null,                   'active' => null],
    ];

    $defaults = ['advisors', 'explore', 'construct', 'improvements', 'military', 'town_crier', 'sidebar'];
    $slots = $selectedDominion->settings['bottom_nav'] ?? $defaults;
@endphp
<nav class="mobile-bottom-nav d-lg-none">
    @foreach ($slots as $key)
        @if ($key === '' || !isset($navLinks[$key]))
            {{-- empty slot --}}
        @elseif ($key === 'sidebar')
            <a class="mobile-bottom-nav-item" href="#" data-lte-toggle="sidebar" role="button">
                <i class="{{ $navLinks[$key]['icon'] }}"></i>
            </a>
        @elseif ($key === 'construct')
            <a class="mobile-bottom-nav-item {{ Route::is('dominion.construct') || Route::is('dominion.protection.buildings') ? 'active' : '' }}"
               href="{{ $selectedDominion->isBuildingPhase() ? route('dominion.protection.buildings') : route('dominion.construct') }}">
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

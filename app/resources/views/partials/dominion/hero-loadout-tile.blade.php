@if ($option !== null)
    @php($description = $heroHelper->getLoadoutOptionDescription($option))
    @php($rulesTooltip = $heroHelper->getLoadoutOptionRulesTooltip($option))
    <div class="hero-tile" title="{{ e($description) . ($rulesTooltip !== null ? '<br>' . $rulesTooltip : '') }}" data-bs-toggle="tooltip" tabindex="0">
        <i class="ra {{ $option->icon }}"></i>
        <div class="hero-tile-label">{{ $slot->label() }}</div>
        <div class="hero-tile-name">{{ $heroHelper->getLoadoutOptionName($option) }}</div>
        <div class="hero-tile-description">{{ $description }}</div>
    </div>
@elseif ($available)
    <a href="#hero-loadout" class="hero-tile hero-tile-open" data-loadout-slot="{{ $slot->value }}">
        <i class="fa-regular fa-circle-question"></i>
        <div class="hero-tile-label">{{ $slot->label() }}</div>
        <div class="hero-tile-name">Empty slot</div>
        <div class="hero-tile-description">View {{ strtolower($slot->label()) }} options</div>
    </a>
@else
    <div class="hero-tile hero-tile-locked">
        <i class="ra ra-padlock"></i>
        <div class="hero-tile-label">{{ $slot->label() }}</div>
        <div class="hero-tile-name">Locked</div>
        <div class="hero-tile-description">{{ ($enabled ?? true) ? $heroHelper->getLoadoutSlotRequirement($slot, $index ?? 0) : 'Coming soon' }}</div>
    </div>
@endif

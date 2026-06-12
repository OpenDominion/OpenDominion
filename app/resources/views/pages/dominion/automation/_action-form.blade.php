@php
    $isLocked = $selectedDominion->isLocked();
    $selectedAction = $item['action'] ?? 'train';
@endphp
<div class="action-form-container" id="{{ $formId }}">
    <div class="mb-2">
        Action:
        <select class="form-select" name="action" {{ $isLocked ? 'disabled' : null }}>
            <option value="train" {{ $selectedAction == 'train' ? 'selected' : null }}>Train Military</option>
            <option value="construct" {{ $selectedAction == 'construct' ? 'selected' : null }}>Construct Buildings</option>
            <option value="explore" {{ $selectedAction == 'explore' ? 'selected' : null }}>Explore Land</option>
            <option value="rezone" {{ $selectedAction == 'rezone' ? 'selected' : null }}>Rezone Land</option>
            <option value="spell" {{ $selectedAction == 'spell' ? 'selected' : null }}>Cast Spell</option>
            <option value="release" {{ $selectedAction == 'release' ? 'selected' : null }}>Release Draftees</option>
            <option value="draft_rate" {{ $selectedAction == 'draft_rate' ? 'selected' : null }}>Set Draft Rate</option>
            <option value="daily_bonus" {{ $selectedAction == 'daily_bonus' ? 'selected' : null }}>Daily Bonus</option>
        </select>
    </div>
    <div class="mb-2 action-options train" style="{{ $selectedAction != 'train' ? 'display: none;' : '' }}">
        Unit:
        <select class="form-select" name="key" {{ ($selectedAction != 'train' || $isLocked) ? 'disabled' : null }}>
            <option></option>
            @foreach ($unitTypes as $unitType)
                <option value="{{ $unitType }}" {{ ($item && $selectedAction == 'train' && ($item['key'] ?? '') == $unitType) ? 'selected' : null }}>
                    {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="mb-2 action-options construct" style="{{ $selectedAction != 'construct' ? 'display: none;' : '' }}">
        Building:
        <select class="form-select" name="key" {{ ($selectedAction != 'construct' || $isLocked) ? 'disabled' : null }}>
            <option></option>
            @foreach ($buildings as $building)
                <option value="{{ $building }}" {{ ($item && $selectedAction == 'construct' && ($item['key'] ?? '') == $building) ? 'selected' : null }}>
                    {{ $buildingHelper->getBuildingName($building) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="mb-2 action-options explore rezone" style="{{ !in_array($selectedAction, ['explore', 'rezone']) ? 'display: none;' : '' }}">
        Land Type:
        <select class="form-select" name="key" {{ (!in_array($selectedAction, ['explore', 'rezone']) || $isLocked) ? 'disabled' : null }}>
            <option></option>
            @foreach ($landTypes as $landType)
                <option value="{{ $landType }}" {{ ($item && in_array($selectedAction, ['explore', 'rezone']) && ($item['key'] ?? '') == $landType) ? 'selected' : null }}>
                    {{ ucwords($landType) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="mb-2 action-options rezone" style="{{ $selectedAction != 'rezone' ? 'display: none;' : '' }}">
        Target Land Type:
        <select class="form-select" name="key2" {{ ($selectedAction != 'rezone' || $isLocked) ? 'disabled' : null }}>
            <option></option>
            @foreach ($landTypes as $landType)
                <option value="{{ $landType }}" {{ ($item && $selectedAction == 'rezone' && ($item['key2'] ?? '') == $landType) ? 'selected' : null }}>
                    {{ ucwords($landType) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="mb-2 action-options train construct explore rezone release draft_rate" style="{{ in_array($selectedAction, ['spell', 'daily_bonus']) ? 'display: none;' : '' }}">
        Amount:
        <input type="number" name="amount" class="form-control form-control-sm" placeholder="Amount" min="0"
               value="{{ $item['amount'] ?? '' }}"
               {{ (in_array($selectedAction, ['spell', 'daily_bonus']) || $isLocked) ? 'disabled' : null }} />
    </div>
    <div class="mb-2 action-options spell" style="{{ $selectedAction != 'spell' ? 'display: none;' : '' }}">
        Spell:
        <select class="form-select" name="key" {{ ($selectedAction != 'spell' || $isLocked) ? 'disabled' : null }}>
            <option></option>
            @foreach ($spells as $spell)
                <option value="{{ $spell->key }}" {{ ($item && $selectedAction == 'spell' && ($item['key'] ?? '') == $spell->key) ? 'selected' : null }}>
                    {{ $spell->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="mb-2 action-options daily_bonus" style="{{ $selectedAction != 'daily_bonus' ? 'display: none;' : '' }}">
        Bonus:
        <select class="form-select" name="key" {{ ($selectedAction != 'daily_bonus' || $isLocked) ? 'disabled' : null }}>
            <option></option>
            <option value="land" {{ ($item && $selectedAction == 'daily_bonus' && ($item['key'] ?? '') == 'land') ? 'selected' : null }}>Land</option>
            <option value="platinum" {{ ($item && $selectedAction == 'daily_bonus' && ($item['key'] ?? '') == 'platinum') ? 'selected' : null }}>Platinum</option>
        </select>
    </div>
</div>

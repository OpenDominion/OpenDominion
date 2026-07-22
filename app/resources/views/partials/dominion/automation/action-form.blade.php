@php
    $isLocked = $selectedDominion->isLocked();
    $selectedAction = $item['action'] ?? 'train';
    $quickFillId = "{$formId}-quick-fill";
@endphp
<div class="action-form-container" id="{{ $formId }}">
    <div class="automation-quick-fill mb-3" data-quick-fill-root>
        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
            <label class="form-label mb-0" for="{{ $quickFillId }}">Quick fill</label>
            <button class="btn btn-link btn-sm p-1 quick-fill-manage" type="button"
                data-bs-toggle="modal" data-bs-target="#quickFillManagerModal">
                Manage <span class="quick-fill-count">3/5</span>
            </button>
        </div>
        <div class="quick-fill-combobox">
            <input class="form-control" id="{{ $quickFillId }}" type="text"
                role="combobox" aria-autocomplete="list" aria-expanded="false"
                aria-controls="{{ $quickFillId }}-listbox"
                aria-describedby="{{ $quickFillId }}-help {{ $quickFillId }}-status"
                autocomplete="off" autocapitalize="none" spellcheck="false"
                placeholder="e.g. train 120 archers" {{ $isLocked ? 'disabled' : null }}>
            <div class="quick-fill-popover" data-quick-fill-popover hidden>
                <ul class="quick-fill-listbox" id="{{ $quickFillId }}-listbox" role="listbox"
                    aria-label="Quick fill suggestions"></ul>
                <div class="quick-fill-foot" data-quick-fill-foot>Up to 5 current, player-controlled suggestions</div>
            </div>
        </div>
        <div class="form-text" id="{{ $quickFillId }}-help">Type naturally or choose a suggestion. Structured fields remain editable.</div>
        <div class="quick-fill-status" id="{{ $quickFillId }}-status" role="status" aria-live="polite"></div>
        <div class="visually-hidden" data-quick-fill-live role="status" aria-live="polite"></div>
    </div>
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
        <input type="number" name="amount" class="form-control" placeholder="Amount" min="0"
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

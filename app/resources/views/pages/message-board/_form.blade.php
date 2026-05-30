@php
    /** @var \OpenDominion\Models\MessageBoard\Thread|null $thread */
    $thread = $thread ?? null;
    $isEdit = $thread !== null;
    $isAdmin = auth()->user()?->hasRole('Administrator');
    $presets = \OpenDominion\Helpers\AnnouncementPresetHelper::PRESETS;

    $displayChecked = old('homepage_display', $isEdit ? ($thread->homepage_display ? '1' : '0') : '1') == '1';
    $currentPreset = old('homepage_preset', $thread->homepage_preset ?? \OpenDominion\Helpers\AnnouncementPresetHelper::DEFAULT_PRESET);
    $currentSubtitle = old('homepage_subtitle', $thread->homepage_subtitle ?? '');
    $currentUrl = old('homepage_url', $thread->homepage_url ?? '');
@endphp

@if (!$isEdit)
    <div class="row mb-3">
        <label for="category" class="col-sm-3 col-form-label">Category</label>
        <div class="col-sm-9">
            <select name="category" id="category" class="form-select">
                @foreach ($categories as $category)
                    @if ($category->role_required == null || $user->hasRole($category->role_required))
                        <option value="{{ $category->id }}" {{ $category->id == $selectedCategory ? 'selected' : null }}>
                            {{ $category->name }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>
    </div>
@endif

<div class="row mb-3">
    <label for="title" class="col-sm-3 col-form-label">Title</label>
    <div class="col-sm-9">
        <input type="text" name="title" id="title" class="form-control" placeholder="Title" value="{{ old('title', $thread->title ?? '') }}" required autofocus>
    </div>
</div>

<div class="row mb-3">
    <label for="body" class="col-sm-3 col-form-label">Body</label>
    <div class="col-sm-9">
        <textarea name="body" id="body" cols="30" rows="10" class="form-control" placeholder="Body" required>{{ old('body', $thread->body ?? '') }}</textarea>
        <p class="form-text">
            Markdown is supported with <a href="http://commonmark.org/help/" target="_blank">CommonMark syntax <i class="fa fa-external-link"></i></a>.
        </p>
    </div>
</div>

@if ($isAdmin)
    {{-- These fields apply only to threads in the Announcements category. --}}
    <hr>
    <h5 class="mb-3">Homepage Settings <small class="text-muted">(Announcements only)</small></h5>

    <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="homepage_display">Show on homepage</label>
        <div class="col-sm-9">
            <div class="form-check">
                <input type="hidden" name="homepage_display" value="0">
                <input type="checkbox" name="homepage_display" id="homepage_display" value="1" class="form-check-input" {{ $displayChecked ? 'checked' : '' }}>
                <label class="form-check-label" for="homepage_display">Display this thread in the landing-page Chronicle.</label>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <label for="homepage_preset" class="col-sm-3 col-form-label">Homepage preset</label>
        <div class="col-sm-9">
            <select name="homepage_preset" id="homepage_preset" class="form-select">
                @foreach ($presets as $key => $preset)
                    <option value="{{ $key }}" {{ $currentPreset === $key ? 'selected' : '' }}>
                        {{ $preset['label'] }}
                    </option>
                @endforeach
            </select>
            <p class="form-text">Controls the icon and border color on the homepage.</p>
        </div>
    </div>

    <div class="row mb-3">
        <label for="homepage_subtitle" class="col-sm-3 col-form-label">Homepage subtitle</label>
        <div class="col-sm-9">
            <input type="text" name="homepage_subtitle" id="homepage_subtitle" class="form-control" maxlength="255" value="{{ $currentSubtitle }}">
            <p class="form-text">Short teaser shown on the landing page. Defaults to the start of the body.</p>
        </div>
    </div>

    <div class="row mb-3">
        <label for="homepage_url" class="col-sm-3 col-form-label">Homepage URL</label>
        <div class="col-sm-9">
            <input type="url" name="homepage_url" id="homepage_url" class="form-control" maxlength="255" value="{{ $currentUrl }}">
            <p class="form-text">Optional. Defaults to this thread's page.</p>
        </div>
    </div>
@endif

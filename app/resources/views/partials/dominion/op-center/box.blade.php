<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="{{ $titleIconClass }}"></i> {{ $title }}
        </h3>
    </div>

    <div class="box-body {{ (isset($noPadding) && $noPadding) ? 'no-padding' : null }}">
        {{ $slot }}
    </div>

    @isset ($boxFooter)
        <div class="box-footer">
            {{ $boxFooter }}
        </div>
    @endisset
</div>

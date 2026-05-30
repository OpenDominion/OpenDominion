<div class="card card-primary">
    <div class="card-header">
        <span class="card-title">
            <i class="{{ $titleIconClass }}"></i> {{ $title }}
        </span>

        @isset ($opData)
            @isset ($opKey)
                <a class="btn btn-primary ms-2 float-end" style="padding: 0px 4px 0px 4px" onclick="copyJson('{{ $opKey }}')">
                    <i class="fa fa-copy"></i>
                </a>
                <textarea class="d-none" name="{{ $opKey }}" id="{{ $opKey }}">{{ json_encode($opData, JSON_PRETTY_PRINT) }}</textarea>
            @endisset
        @endisset
        @isset ($titleExtra)
            {!! $titleExtra !!}
        @endisset
    </div>

    <div class="card-body {{ (isset($tableResponsive) && !$tableResponsive) ? null : 'table-responsive' }} {{ (isset($noPadding) && $noPadding) ? 'no-padding' : null }}">
        {{ $slot }}
    </div>

    @isset ($boxFooter)
        <div class="card-footer">
            {{ $boxFooter }}
        </div>
    @endisset
</div>

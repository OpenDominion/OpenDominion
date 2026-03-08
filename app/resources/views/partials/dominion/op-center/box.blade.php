<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="{{ $titleIconClass }}"></i> {{ $title }}
        </h3>

        <span class="float-end-container">
            @isset ($opData)
                @isset ($opKey)
                    <a class="btn btn-primary float-end" style="padding: 0px 4px 0px 4px" onclick="copyJson('{{ $opKey }}')">
                        <i class="fa fa-copy"></i>
                    </a>
                    <textarea class="d-none" name="{{ $opKey }}" id="{{ $opKey }}">{{ json_encode($opData, JSON_PRETTY_PRINT) }}</textarea>
                @endisset
            @endisset
                @isset ($titleExtra)
                    {!! $titleExtra !!}
                @endisset
        </span>
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

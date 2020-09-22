<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="{{ $titleIconClass }}"></i> {{ $title }}
        </h3>

        <span class="pull-right-container">
            @isset ($opData)
                @isset ($opKey)
                    <a class="btn btn-primary pull-right" style="padding: 0px 4px 0px 4px" onclick="copyJson('{{ $opKey }}')">
                        <i class="fa fa-copy"></i>
                    </a>
                    <textarea class="hidden" name="{{ $opKey }}" id="{{ $opKey }}">{{ json_encode($opData, JSON_PRETTY_PRINT) }}</textarea>
                @endisset
            @endisset
                @isset ($titleExtra)
                    {!! $titleExtra !!}
                @endisset
        </span>
    </div>

    <div class="box-body {{ (isset($tableResponsive) && !$tableResponsive) ? null : 'table-responsive' }} {{ (isset($noPadding) && $noPadding) ? 'no-padding' : null }}">
        {{ $slot }}
    </div>

    @isset ($boxFooter)
        <div class="box-footer">
            {{ $boxFooter }}
        </div>
    @endisset
</div>

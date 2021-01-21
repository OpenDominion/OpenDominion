@php $techs = $techHelper->getTechs(); @endphp
<svg class="graph" viewBox="0 0 220 220" role="img">
    @foreach ($techs as $tech)
        @foreach ($tech->prerequisites as $prereq)
            @if (isset($techs[$prereq]))
                <line x1="{{ $techHelper->getX($tech) }}" y1="{{ $techHelper->getY($tech) }}" x2="{{ $techHelper->getX($techs[$prereq]) }}" y2="{{ $techHelper->getY($techs[$prereq]) }}" class="edge {{ $tech->key }} {{ $techs[$prereq]->key }}" />
            @endif
        @endforeach
    @endforeach
    @foreach ($techs as $tech)
        <circle r="5" cx="{{ $techHelper->getX($tech) }}" cy="{{ $techHelper->getY($tech) }}"
            id="{{ $tech->key }}"
            class="vertex {{ empty($tech->prerequisites) ? 'active starting' : null }}"
            title="<b>{{ $tech->name }}:</b><br/>{{ $techHelper->getTechDescription($tech, '<br/>') }}"
            data-perks="{!! $techHelper->getTechPerkJSON($tech) !!}" />
    @endforeach
</svg>
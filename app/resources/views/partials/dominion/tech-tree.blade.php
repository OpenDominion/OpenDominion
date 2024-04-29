@php
    if (isset($version)) {
        $techs = $techHelper->getTechs($version);
    } else {
        $techs = $techHelper->getTechs();
    }
@endphp
<svg class="graph" viewBox="0 0 220 220" role="img">
    @foreach ($techs as $tech)
        @foreach ($tech->prerequisites as $prereq)
            @if (isset($techs[$prereq]))
                <line x1="{{ 10 * $tech->x }}" y1="{{ 10 * $tech->y }}" x2="{{ 10 * $techs[$prereq]->x }}" y2="{{ 10 * $techs[$prereq]->y }}" class="edge {{ $tech->key }} {{ $techs[$prereq]->key }}" />
            @endif
        @endforeach
    @endforeach
    @foreach ($techs as $tech)
        <circle r="5" cx="{{ 10 * $tech->x }}" cy="{{ 10 * $tech->y }}"
            id="{{ $tech->key }}"
            class="vertex {{ empty($tech->prerequisites) ? 'active starting' : null }}"
            title="<b>{{ $tech->name }}:</b><br/>{{ $techHelper->getTechDescription($tech, '<br/>') }}"
            data-perks="{!! $techHelper->getTechPerkJSON($tech) !!}" />
    @endforeach
</svg>
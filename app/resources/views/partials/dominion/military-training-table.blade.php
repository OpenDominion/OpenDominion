@php
    $valuePrefix = $isOp ? '~' : '';
@endphp

<table class="table">
    <colgroup>
        <col>
        @for ($i = 1; $i <= 12; $i++)
            <col width="20">
        @endfor
        <col width="100">
    </colgroup>
    <thead>
        <tr>
            <th>Unit</th>
            @for ($i = 1; $i <= 12; $i++)
                <th class="text-center">{{ $i }}</th>
            @endfor
            <th class="text-center">Home (Training)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('draftees', $race, true) }}">
                    Draftees:
                </span>
            </td>
            <td colspan="12">&nbsp;</td>
            <td class="text-center">
                {{ $valuePrefix }}{{ number_format(array_get($data, 'units.home.draftees', 0)) }}
            </td>
        </tr>
        @foreach ($unitHelper->getUnitTypes() as $unitType)
            <tr>
                <td>
                    <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $race, true) }}">
                        {{ $unitHelper->getUnitName($unitType, $race) }}:
                    </span>
                </td>
                @for ($i = 1; $i <= 12; $i++)
                    @php
                        $amount = array_get($data, "units.training.{$unitType}.{$i}", 0);
                    @endphp
                    <td class="text-center">
                        @if ($amount === 0)
                            -
                        @else
                            {{ number_format($amount) }}
                        @endif
                    </td>
                @endfor
                <td class="text-center">
                    @if(array_has($data, "units.home.{$unitType}"))
                        @php
                            $unitsAtHome = (int)array_get($data, "units.home.{$unitType}");
                        @endphp

                        @if ($unitsAtHome === 0)
                            0
                        @else
                            {{ $valuePrefix }}{{ number_format($unitsAtHome) }}
                        @endif
                    @else
                        ???
                    @endif

                    @if ($amountTraining = array_get($data, "units.training.{$unitType}"))
                        ({{ number_format(array_sum($amountTraining)) }})
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
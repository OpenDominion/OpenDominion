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
            <th class="text-center">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach (range(1, 4) as $slot)
            @php
                $unitType = ('unit' . $slot);
            @endphp
            <tr>
                <td>{{ $unitHelper->getUnitName($unitType, $race) }}</td>
                @for ($i = 1; $i <= 12; $i++)
                    @php
                        $amount = array_get($data, "units.returning.{$unitType}.{$i}", 0);
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
                    @if ($amountTraining = array_get($data, "units.returning.{$unitType}"))
                        {{ $valuePrefix }}{{ number_format(array_sum($amountTraining)) }}
                    @else
                        0
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
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
            <th>Building Type</th>
            @for ($i = 1; $i <= 12; $i++)
                <th class="text-center">{{ $i }}</th>
            @endfor
            <th class="text-center">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($buildingHelper->getBuildingTypes() as $buildingType)
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $buildingType)) }}</td>
                @for ($i = 1; $i <= 12; $i++)
                    @php
                        $amount = array_get($data, "constructing.{$buildingType}.{$i}", 0);
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
                    @if ($amountConstructing = array_get($data, "constructing.{$buildingType}"))
                        {{ number_format(array_sum($amountConstructing)) }}
                    @else
                        0
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
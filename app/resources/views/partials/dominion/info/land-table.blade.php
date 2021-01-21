<table class="table">
    <colgroup>
        <col>
        <col width="50">
        <col width="50">
        <col width="50">
        <col width="50">
        <col width="50">
    </colgroup>
    <thead>
        <tr>
            <th>Land Type</th>
            <th class="text-center">Barren</th>
            <th class="text-center" colspan="2">Constructed</th>
            <th class="text-center" colspan="2">Total</th>
        </tr>
    </thead>
    <tbody>
        @php
            $showTotalRow = array_key_exists('totalLand', $data);
        @endphp
        @foreach ($landHelper->getLandTypes() as $landType)
            @php
                if($showTotalRow) {
                    $constructedForLandType = array_get($data, "explored.{$landType}.constructed");
                    $landTypeConstructedPercentageOfTotal = array_get($data, "explored.{$landType}.constructedPercentage");
                }
            @endphp
            <tr>
                <td>
                    {{ ucfirst($landType) }}
                    @if ($landType === $race->home_land_type)
                        <small class="text-muted"><i>(home)</i></small>
                    @endif
                </td>
                <td class="text-center">{{ number_format(array_get($data, "explored.{$landType}.barren")) }}</td>
                <td class="text-center">
                    @if($showTotalRow)
                        {{ number_format($constructedForLandType) }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-left">
                    @if($showTotalRow)
                        <small>({{ number_format($landTypeConstructedPercentageOfTotal, 2) }}%)</small>
                    @endif
                </td>
                <td class="text-center">{{ number_format(array_get($data, "explored.{$landType}.amount")) }}</td>
                <td class="text-left"><small>({{ number_format(array_get($data, "explored.{$landType}.percentage"), 2) }}%)</small></td>
            </tr>
        @endforeach
        @if($showTotalRow)
            <tr>
                <td>
                    Total
                </td>
                <td class="text-center">{{ number_format(array_get($data, "totalBarrenLand")) }}</td>
                <td class="text-center">{{ number_format(array_get($data, "totalConstructedLand")) }}</td>
                <td class="text-center"></td>
                <td class="text-center">{{ number_format(array_get($data, "totalLand")) }}</td>
                <td class="text-center"></td>
            </tr>
        @endif
    </tbody>
</table>
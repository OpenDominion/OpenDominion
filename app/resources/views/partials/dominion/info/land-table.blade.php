<table class="table">
    <colgroup>
        <col>
        <col width="100">
        <col width="100">
        <col width="100">
        <col width="100">
        <col width="100">
    </colgroup>
    <thead>
        <tr>
            <th>Land Type</th>
            <th class="text-center">Barren</th>
            <th class="text-center">Constructed</th>
            <th class="text-center">Constructed%</th>
            <th class="text-center">Total</th>
            <th class="text-center">Total%</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totalLand = 0;
            $totalBarren = 0;
            $totalConstructed = 0;
            foreach ($landHelper->getLandTypes() as $landType) {
                $landTypeAmount = array_get($data, "explored.{$landType}.amount");
                $landTypeBarren = array_get($data, "explored.{$landType}.barren");

                $totalLand += $landTypeAmount;
                $totalBarren += $landTypeBarren;
                $totalConstructed += $landTypeAmount - $landTypeBarren;
            }
        @endphp

        @foreach ($landHelper->getLandTypes() as $landType)
            @php
                $constructedForLandType = array_get($data, "explored.{$landType}.amount") - array_get($data, "explored.{$landType}.barren");
                $landTypeConstructedPercentageOfTotal = 0;

                if($totalConstructed > 0) {
                    $landTypeConstructedPercentageOfTotal = ($constructedForLandType / $totalConstructed) * 100;
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
                <td class="text-center">{{ number_format($constructedForLandType) }}</td>
                <td class="text-center">{{ number_format($landTypeConstructedPercentageOfTotal, 2) }}%</td>
                <td class="text-center">{{ number_format(array_get($data, "explored.{$landType}.amount")) }}</td>
                <td class="text-center">{{ number_format(array_get($data, "explored.{$landType}.percentage"), 2) }}%</td>
            </tr>
        @endforeach
        <tr>
            <td>
                Total
            </td>
            <td class="text-center">{{ number_format($totalBarren) }}</td>
            <td class="text-center">{{ number_format($totalConstructed) }}</td>
            <td class="text-center">100.00%</td>
            <td class="text-center">{{ number_format($totalLand) }}</td>
            <td class="text-center">100.00%</td>
        </tr>
    </tbody>
</table>
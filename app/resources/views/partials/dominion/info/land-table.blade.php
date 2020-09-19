<table class="table">
    <colgroup>
        <col>
        <col width="100">
        <col width="100">
        <col width="100">
    </colgroup>
    <thead>
        <tr>
            <th>Land Type</th>
            <th class="text-center">Number</th>
            <th class="text-center">% of total</th>
            <th class="text-center">Barren</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totalLand = 0;
            $totalBarren = 0;
        @endphp
        @foreach ($landHelper->getLandTypes() as $landType)
            @php
                $totalLand += array_get($data, "explored.{$landType}.amount");
                $totalBarren += array_get($data, "explored.{$landType}.barren");
            @endphp
            <tr>
                <td>
                    {{ ucfirst($landType) }}
                    @if ($landType === $race->home_land_type)
                        <small class="text-muted"><i>(home)</i></small>
                    @endif
                </td>
                <td class="text-center">{{ number_format(array_get($data, "explored.{$landType}.amount")) }}</td>
                <td class="text-center">{{ number_format(array_get($data, "explored.{$landType}.percentage"), 2) }}%</td>
                <td class="text-center">{{ number_format(array_get($data, "explored.{$landType}.barren")) }}</td>
            </tr>
        @endforeach
        <tr>
            <td>
                Total
            </td>
            <td class="text-center">{{ number_format($totalLand) }}</td>
            <td class="text-center">100.00%</td>
            <td class="text-center">{{ number_format($totalBarren) }}</td>
        </tr>
    </tbody>
</table>
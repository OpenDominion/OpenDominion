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
        @foreach ($landHelper->getLandTypes() as $landType)
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
    </tbody>
</table>
<table class="table">
    <colgroup>
        <col>
        <col width="100">
        <col width="100">
    </colgroup>
    <thead>
        <tr>
            <th>Building Type</th>
            <th class="text-center">Number</th>
            <th class="text-center">% of land</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($buildingHelper->getBuildingTypes() as $buildingType)
            @php
                $amount = array_get($data, "constructed.{$buildingType}");
            @endphp
            <tr>
                <td>
                                        <span data-toggle="tooltip" data-placement="top" title="{{ $buildingHelper->getBuildingHelpString($buildingType) }}">
                                            {{ ucwords(str_replace('_', ' ', $buildingType)) }}
                                        </span>
                    {!! $buildingHelper->getBuildingImplementedString($buildingType) !!}
                </td>
                <td class="text-center">{{ number_format($amount) }}</td>
                <td class="text-center">{{ number_format((($amount / array_get($data, "total_land", 0)) * 100), 2) }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>
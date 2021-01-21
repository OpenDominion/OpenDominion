<table class="table">
    <colgroup>
        <col>
        <col width="50">
        <col width="50">
        <col width="50">
        <col width="50">
    </colgroup>
    <thead>
        <tr>
            <th>Building Type</th>
            <th class="text-center" colspan="2">Constructed</th>
            <th class="text-center" colspan="2">With incoming</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totalLand = array_get($data, "total_land", 250);
            $totalConstructedLand = 0;
            $totalConstructedLandWithIncoming = 0;
        @endphp
        @foreach ($buildingHelper->getBuildingTypes() as $buildingType)
            @php
                $amount = array_get($data, "constructed.{$buildingType}");
                $totalConstructedLand += $amount;

                $amountWithIncoming = $amount;

                if ($amountConstructing = array_get($data, "constructing.{$buildingType}")) {
                    $amountWithIncoming += array_sum($amountConstructing);
                }

                $totalConstructedLandWithIncoming += $amountWithIncoming;
            @endphp
            <tr>
                <td>
                    <span data-toggle="tooltip" data-placement="top" title="{{ $buildingHelper->getBuildingHelpString($buildingType) }}">
                        {{ ucwords(str_replace('_', ' ', $buildingType)) }}
                    </span>
                    {!! $buildingHelper->getBuildingImplementedString($buildingType) !!}
                </td>
                <td class="text-center">{{ number_format($amount) }}</td>
                <td class="text-left"><small>({{ number_format((($amount / $totalLand) * 100), 2) }}%)</small></td>
                <td class="text-center">{{ number_format($amountWithIncoming) }}</td>
                <td class="text-left"><small>({{ number_format((($amountWithIncoming / $totalLand) * 100), 2) }}%)</small></td>
            </tr>
        @endforeach
        <tr>
            <td>Total</td>
            <td class="text-center">{{ number_format($totalConstructedLand) }}</td>
            <td class="text-left"><small>({{ number_format(($totalConstructedLand / $totalLand) * 100, 2) }}%)</small></td>

            <td class="text-center">{{ number_format($totalConstructedLandWithIncoming) }}</td>
            <td class="text-left"><small>({{ number_format(($totalConstructedLandWithIncoming / $totalLand) * 100, 2) }}%)</small></td>
        </tr>
    </tbody>
</table>
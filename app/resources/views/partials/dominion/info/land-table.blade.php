<table class="table">
    <colgroup>
        <col>
        <col width="50">
        <col width="50">
        <col width="50">
        <col width="50">
        <col width="50">
        <col width="50">
    </colgroup>
    <thead>
        <tr>
            <th>Land Type</th>
            <th class="text-center" colspan="2">Barren</th>
            <th class="text-center" colspan="2">Total</th>
            <th class="text-center" colspan="2">With incoming</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totalLand = 0;
            $hasTotal = array_key_exists("totalLand", $data);
            if ($hasTotal) {
                $totalLand += array_get($data, "totalLand");
            } else {
                foreach (array_get($data, "explored", []) as $currentLandType) {
                    $totalLand += $currentLandType['amount'];
                }
            }
            $totalLandWithIncoming = $totalLand;
            foreach (array_get($data, "incoming", []) as $incomingLand) {
                $totalLandWithIncoming += array_sum($incomingLand);
            }
        @endphp
        @foreach ($landHelper->getLandTypes() as $landType)
            @php
                $barren = array_get($data, "explored.{$landType}.barren");
                $amount = array_get($data, "explored.{$landType}.amount");

                $amountWithIncoming = $amount;
                if ($amountIncoming = array_get($data, "incoming.{$landType}")) {
                    $amountWithIncoming += array_sum($amountIncoming);
                }
            @endphp
            <tr>
                <td>
                    {{ ucfirst($landType) }}
                    @if ($landType === $race->home_land_type)
                        <small class="text-muted"><i>(home)</i></small>
                    @endif
                </td>
                <td class="text-center">
                    {{ number_format($barren) }}
                </td>
                <td class="text-center">
                    <small>({{ number_format(($barren / $totalLand) * 100, 2) }}%)</small>
                </td>
                <td class="text-center">
                    {{ number_format(array_get($data, "explored.{$landType}.amount")) }}
                </td>
                <td class="text-left">
                    <small>({{ number_format(array_get($data, "explored.{$landType}.percentage"), 2) }}%)</small>
                </td>
                <td class="text-center">
                    {{ number_format($amountWithIncoming) }}
                </td>
                <td class="text-left">
                    <small>({{ number_format(($amountWithIncoming / $totalLandWithIncoming) * 100, 2) }}%)</small>
                </td>
            </tr>
        @endforeach
        <tr>
            <td>Total</td>
            <td class="text-center">{{ number_format(array_get($data, "totalBarrenLand")) }}</td>
            <td class="text-center"></td>
            <td class="text-center">{{ number_format($totalLand) }}</td>
            <td class="text-center"></td>
            <td class="text-center">{{ number_format($totalLandWithIncoming) }}</td>
            <td class="text-center"></td>
        </tr>
    </tbody>
</table>
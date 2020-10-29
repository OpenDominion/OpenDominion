<table class="table">
    <colgroup>
        <col width="150">
        <col>
        <col width="100">
        <col width="200">
    </colgroup>
    <thead>
        <tr>
            <th class="text-right">Total Bonus</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        @php
            $techPerkStrings = $techHelper->getTechPerkStrings();
            $techBonuses = [];
            foreach ($data as $techKey => $techName) {
                $tech = OpenDominion\Models\Tech::where('key', $techKey)->firstOrFail();
                foreach ($tech->perks as $perk) {
                    if (isset($techBonuses[$perk->key])) {
                        $techBonuses[$perk->key] += $perk->pivot->value;
                    } else {
                        $techBonuses[$perk->key] = $perk->pivot->value;
                    }
                }
            }
            ksort($techBonuses);
        @endphp
        @foreach ($techBonuses as $techBonus => $techValue)
            @php
                if ($techValue < 0) {
                    $techPerkString = sprintf($techPerkStrings[$techBonus], $techValue);
                } else {
                    $techPerkString = sprintf($techPerkStrings[$techBonus], '+'.$techValue);
                }
                $techPerk = explode(' ', $techPerkString, 2);
            @endphp
            <tr>
                <td class="text-right">{{ $techPerk[0] }}</td>
                <td>{{ $techPerk[1] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
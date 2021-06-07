<table class="table table-condensed">
    <colgroup>
        <col width="150">
        <col>
    </colgroup>
    <thead>
        <tr>
            <th class="text-right">Total Bonus</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        @php
            $allTechs = OpenDominion\Models\Tech::with('perks')->get();
            $techPerkStrings = $techHelper->getTechPerkStrings();
            $techBonuses = [];
            foreach ($data as $techKey => $techName) {
                $tech = $allTechs->where('key', $techKey)->first();
                if ($tech !== null) {
                    foreach ($tech->perks as $perk) {
                        if (isset($techBonuses[$perk->key])) {
                            $techBonuses[$perk->key] += $perk->pivot->value;
                        } else {
                            $techBonuses[$perk->key] = $perk->pivot->value;
                        }
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
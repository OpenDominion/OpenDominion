<table class="table">
    <colgroup>
        <col width="150">
        <col>
    </colgroup>
    <thead>
        <tr>
            <th>Tech</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        @php
            $techs = OpenDominion\Models\Tech::with('perks')->get()->keyBy('key');
        @endphp
        @foreach ($data as $techKey => $techName)
            @php
                $techDescription = $techHelper->getTechDescription($techs[$techKey]);
            @endphp
            <tr>
                <td>{{ $techName }}</td>
                <td>{{ $techDescription }}</td>
            </tr>
        @endforeach
        <tr>
            <td><b>Total Unlocked</b></td>
            <td>{{ count($data) }}</td>
        </tr>
        @if (!empty($data))
            <tr>
                <td colspan=2>
                    <a href="{{ route('scribes.techs') }}?{{ implode('&', array_map(function($key) { return str_replace('tech_', '', $key); }, array_keys($data))) }}" target="_blank">
                        View as Interactive Tree
                    </a>
                </td>
            </tr>
        @endif
    </tbody>
</table>
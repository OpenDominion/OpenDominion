<table class="table">
    <colgroup>
        <col width="150">
        <col>
        <col width="100">
        <col width="200">
    </colgroup>
    <thead>
        <tr>
            <th>Tech</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $techKey => $techName)
            @php
                $techDescription = $techHelper->getTechDescription(OpenDominion\Models\Tech::where('key', $techKey)->firstOrFail());
            @endphp
            <tr>
                <td>{{ $techName }}</td>
                <td>{{ $techDescription }}</td>
            </tr>
        @endforeach
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
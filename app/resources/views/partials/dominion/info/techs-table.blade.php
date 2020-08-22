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
    </tbody>
</table>
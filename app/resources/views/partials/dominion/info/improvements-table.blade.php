<table class="table">
    <colgroup>
        <col width="150">
        <col>
        <col width="100">
    </colgroup>
    <thead>
        <tr>
            <td>Part</td>
            <td>Rating</td>
            <td class="text-center">Invested <span class="text-muted small">(Incoming)</span></td>
        </tr>
    </thead>
    <tbody>
        @foreach ($improvementHelper->getImprovementTypes() as $improvementType)
            <tr>
                <td>
                    {{ $improvementHelper->getImprovementName($improvementType) }}
                    <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ $improvementHelper->getImprovementHelpString($improvementType) }}"></i>
                </td>
                <td>
                    {!! sprintf(
                        $improvementHelper->getImprovementRatingString($improvementType),
                        number_format((array_get($data, "{$improvementType}.rating") * 100), 2),
                        number_format((array_get($data, "{$improvementType}.rating_secondary", array_get($data, "{$improvementType}.rating") * 1.5) * 100), 2)
                    ) !!}
                </td>
                <td class="text-center">
                    {{ number_format(array_get($data, "{$improvementType}.points")) }}
                    @if (array_get($data, "{$improvementType}.incoming"))
                        <span class="text-muted small">({{ number_format(array_get($data, "{$improvementType}.incoming")) }})</span>
                    @endif
                </td>
            </tr>
        @endforeach
        <tr>
            <td>Total</td>
            <td>
                @if (isset($data['total']) && isset($data['highest_total']) && $data['highest_total'] > $data['total'])
                    <span class="text-muted">
                        ({{ number_format($data['highest_total'] - $data['total']) }} damage)
                    </span>
                @endif
            </td>
            <td class="text-center">
                {{ number_format(array_sum(array_column($data, 'points'))) }}
            </td>
    </tbody>
</table>
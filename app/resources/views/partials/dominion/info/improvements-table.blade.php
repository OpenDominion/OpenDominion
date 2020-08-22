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
            <td class="text-center">Invested</td>
        </tr>
    </thead>
    <tbody>
        @foreach ($improvementHelper->getImprovementTypes() as $improvementType)
            <tr>
                <td>
                    {{ ucfirst($improvementType) }}
                    <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ $improvementHelper->getImprovementHelpString($improvementType) }}"></i>
                </td>
                <td>
                    {{ sprintf(
                        $improvementHelper->getImprovementRatingString($improvementType),
                        number_format((array_get($data, "{$improvementType}.rating") * 100), 2),
                        number_format((array_get($data, "{$improvementType}.rating") * 100 * 2), 2)
                    ) }}
                </td>
                <td class="text-center">{{ number_format(array_get($data, "{$improvementType}.points")) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
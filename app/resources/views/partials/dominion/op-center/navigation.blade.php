@php
    $informationOperations = [
        'clear_sight' => ['anchor' => 'op-clear-sight', 'label' => 'Clear Sight'],
        'revelation' => ['anchor' => 'op-revelation', 'label' => 'Revelation'],
        'castle_spy' => ['anchor' => 'op-castle-spy', 'label' => 'Castle Spy'],
        'barracks_spy' => ['anchor' => 'op-barracks-spy', 'label' => 'Barracks Spy'],
        'survey_dominion' => ['anchor' => 'op-survey-dominion', 'label' => 'Survey Dominion'],
        'land_spy' => ['anchor' => 'op-land-spy', 'label' => 'Land Spy'],
        'vision' => ['anchor' => 'op-vision', 'label' => 'Vision'],
        'disclosure' => ['anchor' => 'op-disclosure', 'label' => 'Disclosure'],
    ];
    $isArchiveNavigation = isset($archiveDominion);
    $activeInfoOpType = $activeInfoOpType ?? null;
@endphp

<nav class="op-center-navigation card shadow-sm mb-3" aria-label="Jump to an information operation">
    <div class="card-body d-flex align-items-center gap-2 p-2">
        <span class="text-body-secondary text-nowrap" aria-hidden="true">
            <i class="fa fa-binoculars me-sm-1"></i>
            <span class="d-none d-sm-inline">Jump to</span>
        </span>

        <div class="op-center-navigation-scroller">
            <div class="d-flex gap-2">
                @foreach ($informationOperations as $type => $operation)
                    @php
                        $isActiveArchive = $isArchiveNavigation && $activeInfoOpType === $type;
                        $href = $isArchiveNavigation
                            ? route('dominion.op-center.archive', [$archiveDominion, $type])
                            : '#' . $operation['anchor'];
                    @endphp
                    <a
                        @class([
                            'btn',
                            'btn-sm',
                            'btn-outline-primary',
                            'active' => $isActiveArchive,
                        ])
                        href="{{ $href }}"
                        @if ($isActiveArchive) aria-current="page" @endif
                    >{{ $operation['label'] }}</a>
                @endforeach
            </div>
        </div>
    </div>
</nav>

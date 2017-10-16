<footer class="main-footer">

    <div class="pull-right">
        @if (isset($selectedDominion) && ($selectedDominion->round->isActive()))
            @php
            $diff = $selectedDominion->round->start_date->diff(Carbon\Carbon::now());
            $roundDurationInDays = $selectedDominion->round->start_date->diffInDays($selectedDominion->round->end_date);

            echo "Day <strong>{$diff->days}</strong>/{$roundDurationInDays}, hour <strong>{$diff->h}</strong>.";

            @endphp
        @endif
        <span class="hidden-xs">Version: </span>{!! $version !!}
    </div>

    <i class="fa fa-github"></i> <span class="hidden-xs">View this project on </span><a href="https://github.com/WaveHack/OpenDominion" target="_blank">GitHub <i class="fa fa-external-link"></i></a>

</footer>

<footer class="app-footer">
    <div class="row">

        <div class="col-6">
            <span class="d-none d-sm-inline">Version: </span>{!! $version !!}
            &nbsp;|&nbsp;
            <span class="d-none d-lg-inline"><i class="fa fa-github"></i> View this project on </span><a href="https://github.com/OpenDominion/OpenDominion" target="_blank">GitHub <i class="fa fa-external-link"></i></a>
        </div>

        <div class="col-6 text-end">
            @if (config('app.discord_report_webhook'))
                <a href="#" data-bs-toggle="modal" data-bs-target="#reportModal">Report a Problem</a>
            @endif

            @if (isset($selectedDominion) && ($selectedDominion->round->isActive()))
                @if (config('app.discord_report_webhook'))
                    &nbsp;|&nbsp;
                @endif
                @php
                    $roundDay = $selectedDominion->round->daysInRound();
                    $roundDurationInDays = $selectedDominion->round->durationInDays();
                    $currentHour = $selectedDominion->round->hoursInDay();
                @endphp
                <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ now() }}">
                    Day <strong>{{ $roundDay }}</strong>/{{ $roundDurationInDays }}, Hour <strong>{{ $currentHour }}</strong>
                </span>
            @endif
        </div>

    </div>
</footer>

<div class="modal fade" id="reportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('dominion.misc.report') }}" method="POST" role="form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Report a Problem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="type" class="form-label">Type:</label>
                        <select name="type" class="form-control" id="type">
                            <option value="bug">Bug Report</option>
                            <option value="abuse">Cheating/Abuse</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description:</label>
                        <textarea name="description" class="form-control" id="description" rows="5"></textarea>
                        <div class="form-text">Please be as detailed as possible to help us address the issue.</div>
                        <div class="form-text">We investigate every issue. If the situation calls for it, you may receive a response (to the email address associated with your account), but this is rare.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

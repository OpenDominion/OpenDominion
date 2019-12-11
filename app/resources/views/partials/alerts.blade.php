@if (isset($selectedDominion) && !Route::is('home'))
    @if ($selectedDominion->isLocked())
        <div class="alert alert-warning">
            @if ($selectedDominion->is_locked)
                <p><i class="icon fa fa-warning"></i> This dominion is <strong>locked</strong> due to administrative action. No actions can be performed and no ticks will be processed.</p>
            @else
                <p><i class="icon fa fa-warning"></i> This dominion is <strong>locked</strong> due to the round having ended. No actions can be performed and no ticks will be processed.</p>
                <p>Go to your <a href="{{ route('dashboard') }}">dashboard</a> to check if new rounds are open to play.</p>
            @endif
        </div>
    @endif

    @if (!$selectedDominion->round->hasStarted())
        <div class="alert alert-warning">
            <p><i class="fa fa-warning"></i> The round has not yet started, but actions for the first hour can be performed in advance.</p>
        </div>
    @endif
@endif

@if (!$errors->isEmpty())
    <div class="alert alert-danger alert-dismissible">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <h4>One or more errors occurred:</h4>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@foreach (['danger', 'warning', 'success', 'info'] as $alert_type)
    @if (Session::has('alert-' . $alert_type))
        <div class="alert alert-{{ $alert_type }} alert-dismissible">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <p>{{ Session::get('alert-' . $alert_type) }}</p>
        </div>
    @endif
@endforeach

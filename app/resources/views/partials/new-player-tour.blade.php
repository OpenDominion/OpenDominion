@if ($newPlayerTour)
    @if ($newPlayerTour['is_current_page'])
        <section
            class="new-player-tour"
            data-new-player-tour
            data-tour-target="{{ $newPlayerTour['target'] }}"
            data-tour-nav="{{ $newPlayerTour['nav'] }}"
            aria-labelledby="new-player-tour-title"
        >
            <div class="new-player-tour-progress" data-tour-drag-handle title="Drag field guide" aria-label="Guide progress: step {{ $newPlayerTour['number'] }} of {{ $newPlayerTour['total'] }}. Drag to move on desktop.">
                <span>{{ $newPlayerTour['phase_label'] }}</span>
                <span>{{ $newPlayerTour['number'] }} / {{ $newPlayerTour['total'] }}</span>
            </div>
            <div class="new-player-tour-meter" aria-hidden="true">
                <span style="width: {{ $newPlayerTour['progress_percent'] }}%"></span>
            </div>
            <h2 id="new-player-tour-title">{{ $newPlayerTour['title'] }}</h2>
            <p>{{ $newPlayerTour['body'] }}</p>
            @if ($newPlayerTour['quest'])
                <div class="new-player-tour-objective {{ $newPlayerTour['satisfied'] ? 'new-player-tour-objective--complete' : null }}" role="status">
                    <i class="fa {{ $newPlayerTour['satisfied'] ? 'fa-check-circle' : 'fa-circle-o' }}" aria-hidden="true"></i>
                    <span>
                        <strong>{{ $newPlayerTour['satisfied'] ? 'Quest complete' : 'Your objective' }}</strong>
                        {{ $newPlayerTour['satisfied'] ? 'The game recorded this action. Continue when ready.' : $newPlayerTour['objective'] }}
                    </span>
                </div>
            @endif
            <details class="new-player-tour-details">
                <summary>
                    <span>View guide progress</span>
                    <span class="new-player-tour-details-count">{{ collect($newPlayerTour['progress_items'])->where('state', 'completed')->count() }} complete</span>
                </summary>
                <ol>
                    @foreach ($newPlayerTour['progress_items'] as $item)
                        <li class="new-player-tour-progress-item new-player-tour-progress-item--{{ $item['state'] }}">
                            <i class="fa {{ in_array($item['state'], ['completed', 'ready']) ? 'fa-check' : ($item['state'] === 'locked' ? 'fa-lock' : 'fa-circle-o') }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                            <small>{{ ucfirst($item['state']) }}</small>
                        </li>
                    @endforeach
                </ol>
            </details>
            <div class="new-player-tour-actions">
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-outline-primary btn-sm new-player-tour-show-page" data-tour-show-page>
                        <i class="fa fa-crosshairs me-1" aria-hidden="true"></i> Show page
                    </button>
                    @if ($newPlayerTour['action_url'])
                        <a href="{{ $newPlayerTour['action_url'] }}" class="btn btn-outline-primary btn-sm" @if ($newPlayerTour['action_external']) target="_blank" rel="noopener" @endif>
                            <i class="fa {{ $newPlayerTour['action_external'] ? 'fa-external-link' : 'fa-refresh' }} me-1" aria-hidden="true"></i>
                            {{ $newPlayerTour['action_label'] }}
                        </a>
                    @endif
                    <form action="{{ route('new-player-tour.skip') }}" method="post">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm">Skip guide</button>
                    </form>
                </div>
                <div class="d-flex gap-2 ms-auto">
                    @if ($newPlayerTour['number'] > 1)
                        <form action="{{ route('new-player-tour.back') }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Previous</button>
                        </form>
                    @endif
                    @if (!$newPlayerTour['paused'] && (!$newPlayerTour['quest'] || $newPlayerTour['satisfied']))
                        <form action="{{ route('new-player-tour.advance') }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                {{ $newPlayerTour['number'] === $newPlayerTour['total'] ? 'Finish guide' : ($newPlayerTour['quest'] ? 'Continue quest' : 'Continue') }}
                                <i class="fa fa-arrow-right ms-1" aria-hidden="true"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <button type="button" class="new-player-tour-collapse" data-tour-collapse aria-label="Collapse game guide" title="Collapse game guide">
                <i class="fa fa-chevron-down" aria-hidden="true"></i>
            </button>
        </section>
        <button type="button" class="new-player-tour-resume" data-tour-resume hidden>
            <i class="fa fa-compass" aria-hidden="true"></i>
            Resume guide: {{ $newPlayerTour['label'] }}
        </button>
    @elseif ($newPlayerTour['paused'])
        <div class="new-player-tour-resume new-player-tour-resume--paused" role="status">
            <i class="fa fa-hourglass-half" aria-hidden="true"></i>
            Guide resumes after protection
        </div>
    @elseif ($newPlayerTour['quest'] && $newPlayerTour['satisfied'])
        <form class="new-player-tour-resume-form" action="{{ route('new-player-tour.advance') }}" method="post">
            @csrf
            <button class="new-player-tour-resume new-player-tour-resume--complete" type="submit">
                <i class="fa fa-check-circle" aria-hidden="true"></i>
                Quest complete: continue guide
            </button>
        </form>
    @else
        <a class="new-player-tour-resume" href="{{ $newPlayerTour['url'] }}">
            <i class="fa fa-compass" aria-hidden="true"></i>
            Continue guide: {{ $newPlayerTour['label'] }}
        </a>
    @endif
@endif

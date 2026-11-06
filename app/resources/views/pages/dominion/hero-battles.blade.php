@extends('layouts.master')

@section('page-header', 'Hero Battles')

@section('content')
    <div class="row">

        <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-axe"></i> Active Battles</span>
                    @if ($activeBattles->where('finished', false)->count() == 0)
                        <div class="float-end">
                            <a class="btn btn-primary btn-sm" href="{{ route('dominion.heroes.battles.practice') }}">
                                Practice Battles
                            </a>
                            @if ($battleStatus['queued'] !== null)
                                <a class="btn btn-danger btn-sm" href="{{ route('dominion.heroes.battles.dequeue') }}" data-queued="{{ $battleStatus['queued'] }}">
                                    Leave {{ $battleStatus['queued'] }}v{{ $battleStatus['queued'] }} Queue
                                </a>
                            @else
                                @foreach ($battleStatus['waiting'] as $size => $isWaiting)
                                    <a class="btn btn-primary btn-sm" href="{{ route('dominion.heroes.battles.queue', ['size' => $size]) }}" data-queue-size="{{ $size }}">
                                        Queue {{ $size }}v{{ $size }}
                                        @if ($isWaiting)
                                            <span class="badge text-bg-success ms-1" title="A player is waiting in this queue" data-bs-toggle="tooltip" data-queue-waiting>1 waiting</span>
                                        @endif
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    @foreach ($activeBattles as $battle)
                        @include('partials.dominion.hero-battle', ['view' => $battlePresenter->present($battle, $selectedDominion->id), 'showOrders' => true])
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-axe"></i> Previous Battles</span>
                    <div class="float-end">
                        <a href="{{ route('dominion.heroes.battles.leaderboard') }}">View Leaderboard</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Result</th>
                                        <th>Combatants</th>
                                        <th>Winner</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                @foreach ($inactiveBattles as $battle)
                                    <tr>
                                        <td>
                                            @if ($battle->isDraw())
                                                Draw
                                            @elseif ($battle->hasWinningController($selectedDominion->id))
                                                Win
                                            @else
                                                Loss
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('dominion.heroes.battles.report', ['battle'=>$battle->id]) }}">
                                                {{ $battle->matchupLabel() }}
                                            </a>
                                        </td>
                                        <td>{{ $battle->winnerLabel() ?? '--' }}</td>
                                        <td>{{ $battle->created_at }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">How Hero Battles Work</span>
                </div>
                <div class="card-body">
                    @include('partials.dominion.hero-combat')
                </div>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        @if ($pollForChanges)
        // Polls only while the page waits on someone else: a match from the queue, or an
        // opponent's orders.
        (function () {
            var url = @json(route('api.dominion.hero-battles.status'));
            var pollEvery = 5000;
            var pollFor = 2 * 60 * 1000;
            var started = Date.now();

            // While a battle is underway only its turns matter, so queue changes never reload
            // the page in the middle of choosing orders.
            function signature(status) {
                return JSON.stringify(Object.keys(status.battles).length > 0 ? status.battles : status);
            }

            var initial = signature(@json($battleStatus));
            var timer = setInterval(function () {
                if (Date.now() - started > pollFor) {
                    clearInterval(timer);
                    return;
                }
                if (document.hidden) {
                    return;
                }
                fetch(url, { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } })
                    .then(function (response) {
                        return response.ok ? response.json() : null;
                    })
                    .then(function (status) {
                        if (status !== null && signature(status) !== initial) {
                            clearInterval(timer);
                            window.location.reload();
                        }
                    })
                    .catch(function () {});
            }, pollEvery);
        })();
        @endif

        document.querySelectorAll('[data-battle-orders]').forEach(function (box) {
            var form = box.querySelector('form');
            var lock = box.querySelector('[data-orders-lock]');
            var count = box.querySelector('[data-orders-count]');
            var heroes = Array.from(box.querySelectorAll('[data-hero-orders][data-manual]'));

            function refresh() {
                var chosen = heroes.filter(function (hero) {
                    return hero.querySelector('[data-order-action]').value !== '';
                }).length;
                if (count) {
                    count.textContent = chosen + ' of ' + heroes.length + ' chosen';
                }
                lock.disabled = heroes.length === 0 || chosen < heroes.length;
            }

            heroes.forEach(function (hero) {
                var action = hero.querySelector('[data-order-action]');
                var target = hero.querySelector('[data-order-target]');
                var pick = hero.querySelector('[data-pick]');
                var buttons = hero.querySelectorAll('.battle-action');
                var pickers = hero.querySelectorAll('[data-target-pick]');

                function select(button) {
                    buttons.forEach(function (other) {
                        other.classList.toggle('selected', other === button);
                    });
                }

                function choose(button, targetId, label) {
                    action.value = button.dataset.action;
                    target.value = targetId || '';
                    pick.textContent = label;
                    pick.classList.add('chosen');
                    select(button);
                    pickers.forEach(function (picker) {
                        picker.hidden = true;
                    });
                    refresh();
                }

                buttons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        button.dispatchEvent(new MouseEvent('mouseout', { bubbles: true, relatedTarget: document.body }));
                        if (!button.hasAttribute('data-picks')) {
                            choose(button, button.dataset.target, button.dataset.label);
                            return;
                        }
                        action.value = '';
                        target.value = '';
                        pick.textContent = 'Choose';
                        pick.classList.remove('chosen');
                        select(button);
                        pickers.forEach(function (picker) {
                            picker.hidden = picker.dataset.targetPick !== button.dataset.action;
                        });
                        refresh();
                    });
                });

                pickers.forEach(function (picker) {
                    var button = hero.querySelector('.battle-action[data-action="' + picker.dataset.targetPick + '"]');
                    picker.querySelectorAll('[data-target]').forEach(function (option) {
                        option.addEventListener('click', function () {
                            choose(button, option.dataset.target, option.dataset.label);
                        });
                    });
                    picker.querySelector('[data-target-cancel]').addEventListener('click', function () {
                        picker.hidden = true;
                        button.classList.remove('selected');
                    });
                });
            });

            form.addEventListener('submit', function () {
                lock.disabled = true;
            });

            refresh();
        });
    </script>
@endpush

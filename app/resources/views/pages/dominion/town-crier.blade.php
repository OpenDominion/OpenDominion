@extends('layouts.master')

@section('page-header', 'Town Crier')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-newspaper-o"></i> Town Crier for
                        @if ($singleDominion !== null)
                            {{ $singleDominion->name }} (#{{ $singleDominion->realm->number }})
                        @elseif ($realm !== null)
                            {{ $realm->name }} (#{{ $realm->number }})
                        @else
                            All Realms
                        @endif
                    </h3>
                </div>

                @if ($gameEvents->isEmpty())
                    <div class="box-body">
                        <p>No recent events.</p>
                    </div>
                @else
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-striped">
                            <colgroup>
                                <col width="150">
                                <col>
                                <col width="50">
                            </colgroup>
                            <tbody>
                                @php
                                    $previousDate = null;
                                    $firstLoop = true;
                                @endphp
                                @foreach ($gameEvents as $gameEvent)
                                    @if ($previousDate != $gameEvent->created_at->startOfDay())
                                        <tr>
                                            <td colspan="3" class="text-center text-bold border-left border-right">
                                                News from {{ $gameEvent->created_at->toDateString() }}
                                            </td>
                                        </tr>
                                        @php
                                            $previousDate = $gameEvent->created_at->startOfDay();
                                            $firstLoop = false;
                                        @endphp
                                    @endif
                                    @include('partials.dominion.game-event')
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <div class="pull-right">
                            {{ $gameEvents->links() }}
                        </div>
                    </div>
                @endif
                @if ($fromOpCenter)
                    <div class="box-footer">
                        <em>Revealed {{ $clairvoyanceInfoOp->updated_at }} by {{ $clairvoyanceInfoOp->sourceDominion->name }}</em>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    @if ($fromOpCenter)
                        <p>All the news for the target's realm will be shown here.</p>
                    @else
                        <p>All the news for your realm can be seen here.</p>
                    @endif
                    <p>You will see only military operations and important messages regarding Wonders of the World. Magical and Spy operations are not known to the Town Crier.</p>
                    @if ($selectedDominion->round->start_date <= now())
                        @if ($singleDominion == null)
                            <p>
                                <label for="realm-select">Show Town Crier for:</label>
                                <select id="realm-select" class="form-control">
                                    <option value="">All Realms</option>
                                    @for ($i=0; $i<$realmCount; $i++)
                                        <option value="{{ $i }}" {{ $realm && $realm->number == $i ? 'selected' : null }}>
                                            #{{ $i }} {{ $selectedDominion->realm->number == $i ? '(My Realm)' : null }}
                                        </option>
                                    @endfor
                                </select>
                            </p>
                        @endif
                    <p>
                        <label for="realm-select">Event Types:</label>
                        <select id="event-select" class="form-control">
                            @foreach ($typeChoices as $typeChoice)
                                <option value="{{ $typeChoice }}" {{ $type == $typeChoice ? 'selected' : null }}>
                                    {{ ucwords($typeChoice) }}
                                </option>
                            @endforeach
                        </select>
                    </p>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#realm-select').change(function() {
                url = "";

                var selectedRealm = $(this).val();
                if (selectedRealm) {
                    url = selectedRealm + "/";
                }
                var selectedType = $('#event-select').val();
                if (selectedType) {
                    url += "?type=" + selectedType;
                }
                window.location.href = "{!! route('dominion.town-crier') !!}/" + url;
            });
            $('#event-select').change(function() {
                var selectedType = $(this).val();
                window.location.href = "{!! route('dominion.town-crier', $realm->number ?? null) !!}/?type=" + selectedType + "{!! $singleDominion ? '&dominion='.$singleDominion->id : null !!}";
            });
        })(jQuery);
    </script>
@endpush

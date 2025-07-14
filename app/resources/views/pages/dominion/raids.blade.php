@extends('layouts.master')

@section('page-header', 'Raids')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            @if (!empty($raids))
                @foreach ($raids as $raid)
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-castle-flag"></i> {{ $raid->name }}</h3>
                            <div class="pull-right">
                                <span class="badge">
                                    <i class="ra ra-hourglass"></i> {{ $raid->status }}
                                </span>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row form-group">
                                        <div class="col-md-9">
                                            {{ $raid->description }}
                                        </div>
                                        <div class="col-md-3 text-right">
                                            @if (!$raid->hasStarted())
                                                <i class="fa fa-clock-o"></i> Starts in {{ $raid->timeUntilStart() }}
                                            @elseif ($raid->isActive())
                                                <i class="fa fa-clock-o"></i> Ends in {{ $raid->timeUntilEnd() }}
                                            @else
                                                <i class="fa fa-clock-o"></i> Completed
                                            @endif
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-condensed">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Objective</th>
                                                    <th>Description</th>
                                                    <th>Score Required</th>
                                                    <th>Tactics</th>
                                                </tr>
                                            </thead>
                                            @foreach ($raid->objectives->sortBy('order') as $objective)
                                                <tr class="{{ $objective->isActive() ? 'success' : null}}">
                                                    <td>{{ $objective->order }}</td>
                                                    <td>
                                                        <a href="{{ route('dominion.raids.objective', [$objective->id]) }}">
                                                            {{ $objective->name }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $objective->description }}</td>
                                                    <td>{{ number_format($objective->score_required) }}</td>
                                                    <td>
                                                        @foreach ($objective->tactics as $tactic)
                                                            <div class="label label-primary">{{ ucwords($tactic->type) }}</div>
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-castle-flag"></i> Raids</h3>
                    </div>
                    <div class="box-body">
                        There are currently no raids scheduled for this round.
                    </div>
                </div>
            @endif
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Raids are great</p>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            console.log('we need this');
        })(jQuery);
    </script>
@endpush

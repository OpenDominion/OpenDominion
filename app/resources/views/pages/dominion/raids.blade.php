@extends('layouts.master')

@section('page-header', 'Raids')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-castle-flag"></i> Raids</h3>
                </div>
                <div class="box-body">
                    @if (!empty($raids))
                        @foreach ($raids as $raid)
                            <div class="row">
                                <div class="col-md-12">
                                    <h4>{{ $raid->name }}</h4>
                                    <p>{{ $raid->description }}</p>
                                    <table class="table table-condensed">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Objective</th>
                                                <th>Description</th>
                                                <th>Tactics</th>
                                                <th>Score Required</th>
                                            </tr>
                                        </thead>
                                        @foreach ($raid->objectives->sortBy('order') as $objective)
                                            <tr>
                                                <td>{{ $objective->order }}</td>
                                                <td>{{ $objective->name }}</td>
                                                <td>{{ $objective->description }}</td>
                                                <td>
                                                    @foreach ($objective->tactics as $tactic)
                                                        {{ ucwords($tactic->type) }} - {{ $tactic->modifier }}<br/>
                                                    @endforeach
                                                </td>
                                                <td>{{ number_format($objective->score_required) }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p>There are currently no raids available.</p>
                    @endif
                </div>
            </div>
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

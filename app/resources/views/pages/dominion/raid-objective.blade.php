@extends('layouts.master')

@section('page-header', 'Raids')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-castle-flag"></i> {{ $objective->raid->name }}: {{ $objective->name }}</h3>
                    <div class="pull-right">
                        <span class="badge">
                            <i class="ra ra-hourglass"></i> {{ $objective->status }}
                        </span>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row form-group">
                                <div class="col-md-9">
                                    {{ $objective->description }}
                                </div>
                                <div class="col-md-3 text-right">
                                    @if (!$objective->hasStarted())
                                        <i class="fa fa-clock-o"></i> Starts in {{ $objective->timeUntilStart() }}
                                    @elseif ($objective->isActive())
                                        <i class="fa fa-clock-o"></i> Ends in {{ $objective->timeUntilEnd() }}
                                    @else
                                        <i class="fa fa-clock-o"></i> Completed
                                    @endif
                                </div>
                            </div>
                            <div class="progress">
                                <div class="progress-bar progress-bar-green" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 30%">
                                    <span class="sr-only">30% Complete (success)</span>
                                </div>
                                <div class="progress-bar progress-bar-blue" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 10%">
                                    <span class="sr-only">10% Complete (success)</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <b>Progress:</b> 4,000 / 10,000
                                </div>
                                <div class="col-md-6">
                                    <b>Your Contribution:</b> 1,000 / 10,000 (10%)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <form action="" method="post">
                @foreach ($objective->tactics as $tactic)
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box box-warning">
                                <div class="box-header with-border">
                                    {{ $tactic->name }}
                                    <div class="box-tools pull-right">
                                        <div class="label label-primary">{{ ucwords($tactic->type) }}</div>
                                    </div>
                                </div>
                                <div class="box-body">
                                    @include("partials.dominion.raids.{$tactic->type}")
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </form>
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

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Recent Actions</h3>
                </div>
                <div class="box-body">
                    <div>
                        Build Siege Weapons<br/>
                        <span class="small">2 minutes ago</span>
                        <div class="pull-right">+85</div>
                    </div>
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

@extends('layouts.staff')

@section('page-header', "Dominion: {$dominion->name}")

@section('content')
    <div class="row">

        <div class="col-lg-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <div class="box-title">Resource History</div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas id="resourceHistoryChart" style="height: 250px"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <div class="box-title">Population History</div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas id="populationHistoryChart" style="height: 250px"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <div class="box-title">Land History</div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas id="landHistoryChart" style="height: 250px"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <div class="box-title">Building History</div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas id="buildingHistoryChart" style="height: 250px"></canvas>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <pre>{{ print_r(json_decode($dominion), true) }}</pre>
@endsection

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/chart.js/Chart.bundle.min.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var resourceHistoryChart = new Chart($('#resourceHistoryChart').get(0).getContext('2d'), {
                type: 'line',
                data: {!! json_encode($resourceData) !!},
                options: {
                    scales: {
                        xAxes: [{
                            type: 'time',
                            scaleLabel: {
                                display: true,
                                labelString: 'Date',
                            },
                            time: {
                                unit: 'day',
                            },
                        }],
                        yAxes: [{
                            scaleLabel: {
                                display: true,
                                labelString: 'Value',
                            },
                            ticks: {
                                beginAtZero: true,
                            },
                        }]
                    }
                }
            });
        })(jQuery);
    </script>
@endpush

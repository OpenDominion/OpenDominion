@extends('layouts.master')

@section('page-header', 'National Bank')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-capitol"></i> National Bank</h3>
                </div>
                <form action="{{ route('dominion.bank') }}" method="post" role="form">
                    {!! csrf_field() !!}
                    <div class="box-body no-padding">
                        <div class="box-body">Exchange <select id="source" name="source"
                                                               class="form-control form-inline">
                                @foreach($resources as $resourceId => $resource)
                                    @if ($resource['sell'])
                                        <option value="{{ $resourceId }}">{{ $resource['label'] }}</option>
                                    @endif
                                @endforeach
                            </select> for <select id="target" name="target" class="form-control form-inline">
                                @foreach($resources as $resourceId => $resource)
                                    @if ($resource['buy'])
                                        <option value="{{ $resourceId }}">{{ $resource['label'] }}</option>
                                    @endif
                                @endforeach
                            </select></div>

                        <table class="table">
                            <colgroup>
                                <col width="100">
                                <col>
                                <col width="100">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th><span id="sourceResource">{{ reset($resources)['label'] }}</span></th>
                                    <th class="text-center">Amount</th>
                                    <th><span id="targetResource">{{ reset($resources)['label'] }}</span></th>
                                </tr>
                            </thead>
                            <tr>
                                <td>
                                    <input id="sourceAmount" name="amount" type="number"
                                           class="form-control text-center" placeholder="0" min="0"
                                           max="{{ reset($resources)['max'] }}"
                                           value="0" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                </td>
                                <td class="text-center">
                                    <input type="number" value="0"
                                           class="slider form-control" data-slider-min="0"
                                           data-slider-max="{{ reset($resources)['max'] }}" data-slider-step="1"
                                           data-slider-value="0"
                                           data-slider-tooltip="show" data-slider-handle="triangle"
                                           data-slider-id="yellow">
                                </td>
                                <td><span id="targetAmount">0</span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="box-footer">
                        <button class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                            Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The Bank is like a trading outpost, in a sense. The Emperor sponsors the bank, and is willing to
                        let you exchange the resources you have for different resources.</p>
                    <p>You can trade 2 of platinum, ore, or lumber and in return receive 1 of platinum, ore, or lumber.
                        Because the Emperor values gems more highly, he will not let you trade for them, however, he
                        will offer you 2 platinum, ore, or lumber for 1 of your gems. The emperor will also not trade
                        for food, but you can buy food from him for 4 platinum, lumber or ore, or a single gem.</p>
                    <p> Resource management is key to playing the game, and if done perfectly, the bank should be used
                        very rarely. Because the rates are clearly in the bank's favor, you should rely on careful
                        resource management rather than swapping resources at the bank.</p>
                </div>
            </div>
        </div>

    </div>
    @push('page-scripts')
        <script type="text/javascript"
                src="{{ asset('assets/vendor/admin-lte/plugins/bootstrap-slider/bootstrap-slider.js') }}"></script>
    @endpush
    @push('inline-scripts')
        <script type="text/javascript">
            (function ($) {
                const resources = JSON.parse('{!! json_encode($resources) !!}');

                const update = function update() {
                    const currentSource = $("#source").find(":selected");
                    const sourceResource = resources[currentSource.val()];
                    const sourceAmount = Math.min(parseInt($("#sourceAmount").val()), sourceResource['max']);
                    const currentTarget = $("#target").find(":selected");
                    const targetResource = resources[currentTarget.val()];
                    const targetAmount = Math.floor(sourceAmount * sourceResource['sell'] * targetResource['buy']);

                    $("#sourceResource").text(currentSource.text());
                    $("#targetResource").text(currentTarget.text());
                    $("#sourceAmount").attr('max', sourceResource['max']).val(sourceAmount);
                    $("#targetAmount").text(targetAmount.toLocaleString());
                    $("input.slider").slider('setAttribute', 'max', sourceResource['max']);
                    $("input.slider").slider('setValue', sourceAmount);
                }

                $("input.slider").slider({
                    formatter: function (value) {
                        return value.toLocaleString();
                    }
                })
                    .on("change", function (slideEvent) {
                        $("#sourceAmount").val(slideEvent.value.newValue).change();
                    });
                $("#sourceAmount").on("change", update);
                $("#source").on("change", update);
                $("#target").on("change", update);
            })(jQuery);
        </script>
    @endpush
    @push('page-styles')
        <link rel="stylesheet" href="{{ asset('assets/vendor/admin-lte/plugins/bootstrap-slider/slider.css') }}">
    @endpush
@endsection

@extends('layouts.master')

@section('page-header', 'National Bank')

@section('content')
    @php($resources = $bankingCalculator->getResources($selectedDominion))

    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-capitol"></i> National Bank</h3>
                </div>
                <form action="{{ route('dominion.bank') }}" method="post" {{--class="form-inline" --}}role="form">
                    {!! csrf_field() !!}
                    <div class="box-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="row">
                                    <div class="form-group col-sm-6">
                                        <label for="source">Exchange this</label>
                                        <select name="source" id="source" class="form-control" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            @foreach ($resources as $field => $resource)
                                                @if (!$resource['sell'])
                                                    @continue
                                                @endif

                                                <option value="{{ $field }}">{{ $resource['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <label for="target">Into this</label>
                                        <select name="target" id="target" class="form-control" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            @foreach ($resources as $field => $resource)
                                                @if (!$resource['buy'])
                                                    @continue
                                                @endif

                                                <option value="{{ $field }}">{{ $resource['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="row">
                                    <div class="form-group col-sm-3">
                                        <label for="amount" id="amountLabel">{{ reset($resources)['label'] }}</label>
                                        <input type="number"
                                               name="amount"
                                               id="amount"
                                               class="form-control text-center"
                                               value="{{ old('amount') }}"
                                               placeholder="0"
                                               min="0"
                                               max="{{ reset($resources)['max'] }}"
                                                {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <label for="amountSlider">Amount</label>
                                        <input type="number"
                                               id="amountSlider"
                                               class="form-control slider"
                                               {{--value="0"--}}
                                               data-slider-value="0"
                                               data-slider-min="0"
                                               data-slider-max="{{ reset($resources)['max'] }}"
                                               data-slider-step="1"
                                               data-slider-tooltip="show"
                                               data-slider-handle="triangle"
                                               data-slider-id="yellow"
                                                {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </div>
                                    <div class="form-group col-sm-3">
                                        <label id="resultLabel">{{ reset($resources)['label'] }}</label>
                                        <p id="result" class="form-control-static text-center">0</p >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Exchange</button>
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
                    <p>The National Bank allows you to exchange resources with the empire. Exchanging resources processes <b>instantly</b>.</p>
                    <p>Platinum, lumber and ore trade 2 for 1.<br>Gems trade 1:2 platinum, lumber or ore.<br>Food sells for 4 platinum, lumber or ore, or 1 gem.</p>
                    <p>You have {{ number_format($selectedDominion->resource_platinum) }} platinum, {{ number_format($selectedDominion->resource_lumber) }} lumber, {{ number_format($selectedDominion->resource_ore) }} ore and {{ number_format($selectedDominion->resource_gems) }} {{ str_plural('gem', $selectedDominion->resource_gems) }}.</p>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/admin-lte/plugins/bootstrap-slider/slider.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/admin-lte/plugins/bootstrap-slider/bootstrap-slider.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            const resources = JSON.parse('{!! json_encode($resources) !!}');

            // todo: let/const aka ES6 this
            var sourceElement = $('#source'),
                targetElement = $('#target'),
                amountElement = $('#amount'),
                amountLabelElement = $('#amountLabel'),
                amountSliderElement = $('#amountSlider'),
                resultLabelElement = $('#resultLabel'),
                resultElement = $('#result');

            function updateResources() {
                var sourceOption = sourceElement.find(':selected'),
                    sourceResourceType = _.get(resources, sourceOption.val()),
                    sourceAmount = Math.min(parseInt(amountElement.val()), _.get(sourceResourceType, 'max')),
                    targetOption = targetElement.find(':selected'),
                    targetResourceType = _.get(resources, targetOption.val()),
                    targetAmount = (Math.floor(sourceAmount * sourceResourceType['sell'] * targetResourceType['buy']) || 0);

                // Change labels
                amountLabelElement.text(sourceOption.text());
                resultLabelElement.text(targetOption.text());

                // Update amount
                amountElement
                    .attr('max', sourceResourceType['max'])
                    .val(sourceAmount);

                // Update slider
                amountSliderElement
                    .slider('setAttribute', 'max', sourceResourceType['max'])
                    .slider('setValue', sourceAmount);

                // Update target amount
                resultElement.text(targetAmount.toLocaleString());
            }

            sourceElement.on('change', updateResources);
            targetElement.on('change', updateResources);
            amountElement.on('change', updateResources);

            amountSliderElement.slider({
                formatter: function (value) {
                    return value.toLocaleString();
                }
            }).on('change', function (slideEvent) {
                amountElement.val(slideEvent.value.newValue).change();
            });

            updateResources();
        })(jQuery);
    </script>
@endpush

@extends('layouts.master')

@section('page-header', 'National Bank')

@section('content')
    @php($exchangeBonus = $bankingCalculator->getExchangeBonus($selectedDominion))
    @php($resources = $bankingCalculator->getResources($selectedDominion))

    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-money"></i> National Bank</span>
                </div>
                <form action="{{ route('dominion.bank') }}" method="post" {{--class="form-inline" --}}role="form">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-sm-6 col-lg-5">
                                <label for="source">Exchange this</label>
                                <select name="source" id="source" class="form-select" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    @foreach ($resources as $field => $resource)
                                        @if (!$resource['sell'])
                                            @continue
                                        @endif
                                        <option value="{{ $field }}">{{ $resource['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 col-sm-6 col-lg-5">
                                <label for="target">Into this</label>
                                <select name="target" id="target" class="form-select" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    @foreach ($resources as $field => $resource)
                                        @if (!$resource['buy'])
                                            @continue
                                        @endif
                                        <option value="{{ $field }}">{{ $resource['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-sm-3 col-lg-3">
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
                            <div class="mb-3 col-sm-6 col-lg-4">
                                <label for="amountSlider" class="d-flex justify-content-between">
                                    <span>Amount</span>
                                    <span id="amountSliderValue" class="text-muted">0</span>
                                </label>
                                <input type="range"
                                        id="amountSlider"
                                        class="form-range"
                                        value="0"
                                        min="0"
                                        max="{{ reset($resources)['max'] }}"
                                        step="1"
                                        {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                            </div>
                            <div class="mb-3 col-sm-3 col-lg-3">
                                <label id="resultLabel">{{ reset($resources)['label'] }}</label>
                                <input type="number"
                                        id="result"
                                        class="form-control text-center"
                                        value=""
                                        placeholder="0"
                                        min="0"
                                        {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Exchange</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Information</span>
                </div>
                <div class="card-body">
                    <p>The National Bank allows you to exchange resources with the empire. Exchanging resources processes <b>instantly</b>.</p>
                    <p>Platinum, lumber, and ore trade 2:1.<br>Gems can be exchanged 1:2 for platinum, lumber, or ore.<br>Food can be purchased for 4 platinum, lumber, or ore OR 1 gem.</p>
                    <p>You have {{ number_format($selectedDominion->resource_platinum) }} platinum, {{ number_format($selectedDominion->resource_lumber) }} lumber, {{ number_format($selectedDominion->resource_ore) }} ore, and {{ number_format($selectedDominion->resource_gems) }} {{ str_plural('gem', $selectedDominion->resource_gems) }}.</p>
                    @if ($exchangeBonus > 1)
                        <p>Your exchange rate bonus is {{ number_format(($exchangeBonus - 1) * 100) }}%.</p>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            const resources = JSON.parse('{!! json_encode($resources) !!}');

            const sourceElement = $('#source'),
                targetElement = $('#target'),
                amountElement = $('#amount'),
                amountLabelElement = $('#amountLabel'),
                amountSliderElement = $('#amountSlider'),
                amountSliderValueElement = $('#amountSliderValue'),
                resultLabelElement = $('#resultLabel'),
                resultElement = $('#result');

            function updateResources(e, reverse = false) {
                const sourceOption = sourceElement.find(':selected'),
                    sourceResourceType = _.get(resources, sourceOption.val()),
                    targetOption = targetElement.find(':selected'),
                    targetResourceType = _.get(resources, targetOption.val()),
                    resourceMax = _.get(sourceResourceType, 'max');
                let sourceAmount, targetAmount;
                if (reverse) {
                    targetAmount = Math.min(parseInt(resultElement.val() || 0));
                    sourceAmount = (Math.ceil(targetAmount / (sourceResourceType['sell'] * targetResourceType['buy'] * {{ $exchangeBonus }})) || 0);
                    if (sourceAmount > resourceMax) {
                        amountElement.val(resourceMax);
                        updateResources(null, false);
                        return;
                    }
                } else {
                    sourceAmount = Math.min(parseInt(amountElement.val() || 0), resourceMax);
                    targetAmount = (Math.floor(sourceAmount * sourceResourceType['sell'] * targetResourceType['buy'] * {{ $exchangeBonus }}) || 0);
                }

                // Change labels
                amountLabelElement.text(sourceOption.text());
                resultLabelElement.text(targetOption.text());

                // Update amount input
                amountElement
                    .attr('max', sourceResourceType['max'])
                    .val(sourceAmount === 0 ? '' : sourceAmount);

                // Update slider (native range input)
                amountSliderElement
                    .attr('max', sourceResourceType['max'])
                    .val(sourceAmount);
                amountSliderValueElement.text(Number(sourceAmount).toLocaleString());

                // Update target amount
                resultElement.val(targetAmount === 0 ? '' : targetAmount);
            }

            sourceElement.on('change', updateResources);
            targetElement.on('change', updateResources);
            amountElement.on('change', updateResources);
            resultElement.on('change', updateResources.bind(true, true, true));

            amountSliderElement.on('input', function () {
                amountElement.val(this.value).change();
            });
        })(jQuery);
    </script>
@endpush

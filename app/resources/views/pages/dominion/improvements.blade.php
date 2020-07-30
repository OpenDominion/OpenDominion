@extends('layouts.master')

@section('page-header', 'Improvements')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-arrow-up fa-fw"></i> Improvements</h3>
                </div>
                <form action="{{ route('dominion.improvements') }}" method="post" role="form">
                    @csrf
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col width="150">
                                <col>
                                <col width="100">
                                <col width="100">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Part</th>
                                    <th>Rating</th>
                                    <th class="text-center">Invested</th>
                                    <th class="text-center">Invest</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($improvementHelper->getImprovementTypes() as $improvementType)
                                    <tr>
                                        <td>
                                            {{ ucfirst($improvementType) }}
                                            <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ $improvementHelper->getImprovementHelpString($improvementType) }}"></i>
                                        </td>
                                        <td>
                                            {{ sprintf(
                                                $improvementHelper->getImprovementRatingString($improvementType),
                                                number_format($improvementCalculator->getImprovementMultiplierBonus($selectedDominion, $improvementType) * 100, 2),
                                                number_format($improvementCalculator->getImprovementMultiplierBonus($selectedDominion, $improvementType) * 100 * 2, 2)
                                            ) }}
                                        </td>
                                        <td class="text-center">{{ number_format($selectedDominion->{'improvement_' . $improvementType}) }}</td>
                                        <td class="text-center">
                                            <div class="input-group">
                                                <input type="number" name="improve[{{ $improvementType }}]" data-type="{{ $improvementType }}" class="form-control text-center" placeholder="0" min="0" value="{{ old('improve.' . $improvementType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-primary improve-max" data-type="{{ $improvementType }}" type="button">Max</button>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <div class="pull-right">
                            <select name="resource" class="form-control">
                                <option value="platinum" data-amount="{{ $selectedDominion->resource_platinum }}" {{ $selectedResource === 'platinum' ? 'selected' : ''}}>Platinum</option>
                                <option value="lumber" data-amount="{{ $selectedDominion->resource_lumber }}" {{ $selectedResource  === 'lumber' ? 'selected' : ''}}>Lumber</option>
                                <option value="ore" data-amount="{{ $selectedDominion->resource_ore }}" {{ $selectedResource  === 'ore' ? 'selected' : ''}}>Ore</option>
                                <option value="gems" data-amount="{{ $selectedDominion->resource_gems }}" {{ $selectedResource  === 'gems' ? 'selected' : ''}}>Gems</option>
                            </select>
                        </div>

                        <div class="pull-right" style="padding: 7px 8px 0 0">
                            Resource to invest:
                        </div>

                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Invest</button>
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
                    <p>Invest resources in your castle to improve certain parts of your dominion. Improving processes <b>instantly</b>.</p>
                    <p>Resources are converted to points. Each gem is worth 12 points, lumber and ore are worth 2 points and platinum is worth 1 point.</p>
                    <p>You have {{ number_format($selectedDominion->resource_platinum) }} platinum, {{ number_format($selectedDominion->resource_lumber) }} lumber, {{ number_format($selectedDominion->resource_ore) }} ore and {{ number_format($selectedDominion->resource_gems) }} {{ str_plural('gem', $selectedDominion->resource_gems) }}.</p>
                    @if ($selectedDominion->building_masonry > 0)
                        <p>Masonries are increasing your castle improvements by {{ number_format(($improvementCalculator->getImprovementMultiplier($selectedDominion) - 1) * 100, 2) }}%</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Settings</h3>
                </div>
                <form action="{{ route('dominion.improvements') }}" method="post" role="form">
                    @csrf
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col width="50%">
                                <col width="50%">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td class="text-center">Preferred resource:</td>
                                    <td class="text-center">
                                        <select name="preferredresource" class="form-control">
                                            <option value="platinum" {{ $preferredResource === 'platinum' ? 'selected' : ''}}>Platinum</option>
                                            <option value="lumber" {{ $preferredResource  === 'lumber' ? 'selected' : ''}}>Lumber</option>
                                            <option value="ore" {{ $preferredResource  === 'ore' ? 'selected' : ''}}>Ore</option>
                                            <option value="gems" {{ $preferredResource  === 'gems' ? 'selected' : ''}}>Gems</option>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <button type="submit"
                                class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Change
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('.improve-max').click(function(e) {
                var selectedOption = $('select[name=resource] option:selected'),
                    selectedResource = selectedOption.val(),
                    maxAmount = selectedOption.data('amount'),
                    improvementType = $(this).data('type');

                $('input[name=improve\\['+improvementType+'\\]]').val(maxAmount);
            });
        })(jQuery);
    </script>
@endpush

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
                    <div class="box-body no-padding">
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
                                            {!! $improvementHelper->getImprovementImplementedString($improvementType) !!}
                                            <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ $improvementHelper->getImprovementHelpString($improvementType) }}"></i>
                                        </td>
                                        <td>
                                            {{ sprintf(
                                                $improvementHelper->getImprovementRatingString($improvementType),
                                                number_format($improvementCalculator->getImprovementMultiplierBonus($selectedDominion, $improvementType) * 100, 2)
                                            ) }}
                                        </td>
                                        <td class="text-center">{{ number_format($selectedDominion->{'improvement_' . $improvementType}) }}</td>
                                        <td class="text-center">
                                            <input type="number" name="improve[{{ $improvementType }}]" class="form-control text-center" placeholder="0" min="0" value="{{ old('improve.' . $improvementType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <div class="pull-right">
                            <select name="resource" class="form-control">
                                <option value="platinum" {{ $selectedResource === 'platinum' ? 'selected' : ''}}>Platinum</option>
                                <option value="lumber" {{ $selectedResource  === 'lumber' ? 'selected' : ''}}>Lumber</option>
                                <option value="ore" {{ $selectedResource  === 'ore' ? 'selected' : ''}}>Ore</option>
                                <option value="gems" {{ $selectedResource  === 'gems' ? 'selected' : ''}}>Gems</option>
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
                </div>
            </div>
        </div>

    </div>
@endsection

@extends('layouts.master')

@section('page-header', 'Technological Advances')

@section('content')
    @php($unlockedTechs = $selectedDominion->techs->pluck('key')->all())

    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-flask"></i> Technological Advances</h3>
                </div>
                <form action="{{ route('dominion.techs') }}" method="post" role="form">
                    @csrf
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col width="5%">
                                <col width="20%">
                                <col>
                                <col width="20%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>&nbsp;</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Requires</th>
                                </tr>
                            </thead>
                            @foreach ($techs as $tech)
                                <tr class="{{ in_array($tech->key, $unlockedTechs) ? 'text-green' : 'text-default' }}">
                                    <td class="text-center">
                                        @if(in_array($tech->key, $unlockedTechs))
                                            <i class="fa fa-check"></i>
                                        @else
                                            <input type="radio" name="key" id="tech_{{ $tech->key }}" value="{{ $tech->key }}" {{ count(array_diff($tech->prerequisites, $unlockedTechs)) != 0 ? 'disabled' : null }}>
                                        @endif
                                    </td>
                                    <td class="{{ count(array_diff($tech->prerequisites, $unlockedTechs)) != 0 ? 'text-muted' : 'text-default' }}">
                                        <label for="tech_{{ $tech->key }}" style="font-weight: normal;">
                                            {{ $tech->name }}
                                        </label>
                                    </td>
                                    <td>
                                        <label for="tech_{{ $tech->key }}" style="font-weight: normal;">
                                            {{ $techHelper->getTechDescription($tech) }}
                                        </label>
                                    </td>
                                    <td>
                                        @if ($tech->prerequisites)
                                            @foreach ($tech->prerequisites as $key)
                                                {{ $techs[$key]->name }}@if(!$loop->last),<br/>@endif
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                        </table>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ ($techCalculator->getTechCost($selectedDominion) > $selectedDominion->resource_tech || $selectedDominion->isLocked()) ? 'disabled' : null }}>Unlock</button>
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
                    <p>You can obtain technical advancements by reaching appropriate levels of research points. The cost of each advancement scales according to your highest land size or 35% of your total land conqured (whichever is higher) with a minimum of 3780 points. Some advancements require others before you can select them. Please consult the tech tree below.</p>
                    <p>If you pick a tech that has the same bonus as another tech, only the highest technology bonus counts (they do not stack). For example, Military Genius adds +5% offense and Magical Weaponry provides +10% offense. If you obtain both, only the 10% bonus would apply.</p>
                    <p>You have <b>{{ number_format($selectedDominion->resource_tech) }} research points</b> and currently need {{ number_format($techCalculator->getTechCost($selectedDominion)) }} to unlock a new tech.</p>
                    <p>Your highest land achieved is <b>{{ number_format($selectedDominion->highest_land_achieved) }}</b> acres.</p>
                    <p>Your total conquered land total is <b>{{ number_format($selectedDominion->stat_total_land_conquered) }}</b> acres.</p>
                </div>
            </div>
        </div>

    </div>
@endsection

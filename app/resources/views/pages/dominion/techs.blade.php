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
                                <tr class="{{ $techCalculator->hasPrerequisites($selectedDominion, $tech) ? 'text-default' : 'text-muted' }}{{ in_array($tech->key, $unlockedTechs) ? ' text-green' : null }} {{ empty($tech->prerequisites) && !in_array($tech->key, $unlockedTechs) ? 'active' : null }}">
                                    <td class="text-center">
                                        @if(in_array($tech->key, $unlockedTechs))
                                            <i class="fa fa-check"></i>
                                        @else
                                            <input type="radio" name="key" id="tech_{{ $tech->key }}" value="{{ $tech->key }}" {{ $techCalculator->hasPrerequisites($selectedDominion, $tech) ? null : 'disabled' }}>
                                        @endif
                                    </td>
                                    <td>
                                        <label for="tech_{{ $tech->key }}" style="font-weight: normal;">
                                            {{ $tech->name }}
                                        </label>
                                    </td>
                                    <td>
                                        <label for="tech_{{ $tech->key }}" style="font-weight: normal;">
                                            {!! $techHelper->getTechDescription($tech, '<br/>') !!}
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
                    @php($techProgress = min(100, $selectedDominion->resource_tech / $techCalculator->getTechCost($selectedDominion) * 100))
                    <p>You can obtain technical advancements by reaching appropriate levels of research points. The cost of each advancement is {{ number_format($techCalculator->getTechCost($selectedDominion)) }}. Most advancements require unlocking another before you can select them.</p>
                    <p><a href="{{ route('scribes.techs') }}?{{ implode('&', array_map(function($key) { return str_replace('tech_', '', $key); }, $unlockedTechs)) }}">View as Interactive Tree</a> in the Scribes.</p>
                    <p>If you pick a tech that has the same bonus as another tech, you will receive the total bonus from both.</p>
                    <p>You have <b>{{ number_format($selectedDominion->resource_tech) }} research points</b> out of the {{ number_format($techCalculator->getTechCost($selectedDominion)) }} required to unlock a new tech.</p>
                    <div class="progress" style="margin-bottom: 0px;">
                        <div class="progress-bar progress-bar-success" role="progressbar" style="width: {{ number_format($techProgress) }}%">
                            @if ($techProgress > 5)
                                {{ number_format($techProgress, 2) }}%
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

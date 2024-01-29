@extends('layouts.master')

@php
    $target = $selectedDominion;
    $pageHeader = 'Magic Advisor';
    $boxTitle = 'Spells affecting your dominion';
    if($targetDominion != null) {
        $target = $targetDominion;
        $pageHeader .= ' for '.$target->name;
        $boxTitle = 'Spells affecting '.$target->name;
    }
@endphp

@section('page-header', $pageHeader)

@section('content')
    @include('partials.dominion.advisor-selector')
    <div class="row">

        <div class="col-md-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-burning-embers"></i> {{ $boxTitle }}</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <colgroup>
                            <col width="150">
                            <col>
                            <col width="100">
                            <col width="200">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Spell</th>
                                <th>Effect</th>
                                <th class="text-center">Duration</th>
                                <th class="text-center">Cast By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($spellCalculator->getActiveSpells($target) as $activeSpell)
                                <tr>
                                    <td>{{ $activeSpell->spell->name }}</td>
                                    <td>{{ $spellHelper->getSpellDescription($activeSpell->spell) }}</td>
                                    <td class="text-center">{{ $activeSpell->duration }}</td>
                                    <td class="text-center">
                                        @if ($activeSpell->cast_by_dominion_id == $target->id || $selectedDominion->realm->dominions->pluck('id')->contains($activeSpell->cast_by_dominion_id) || $target->getSpellPerkValue('surreal_perception'))
                                            <a href="{{ route('dominion.realm', $activeSpell->castByDominion->realm->number) }}">{{ $activeSpell->castByDominion->name }} (#{{ $activeSpell->castByDominion->realm->number }})</a>
                                        @else
                                            <em>Unknown</em>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            {{-- todo: self-cast magic system --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The magic advisor tells you the current spells affecting your dominion and their remaining duration.</p>
                </div>
            </div>
        </div>

    </div>

@endsection

@extends('layouts.master')

@section('page-header', 'Magic Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')
    @php
        $target = $selectedDominion;

        if($targetDominion != null) {
            $target = $targetDominion;
        }
    @endphp
    <div class="row">

        <div class="col-md-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-burning-embers"></i> Spells affecting your dominion</h3>
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
                            @foreach ($spellCalculator->getActiveSpells($target) as $spell)
                                @php
                                    $spellInfo = $spellHelper->getSpellInfo($spell->spell, $target->race);
                                @endphp
                                <tr>
                                    <td>{{ $spellInfo['name'] }}</td>
                                    <td>{{ $spellInfo['description'] }}</td>
                                    <td class="text-center">{{ $spell->duration }}</td>
                                    <td class="text-center">
                                        @if ($spell->cast_by_dominion_id !== null && ($spell->cast_by_dominion_id == $target->id || $spellCalculator->isSpellActive($target, 'surreal_perception')))
                                            <a href="{{ route('dominion.realm', $spell->cast_by_dominion_realm_number) }}">{{ $spell->cast_by_dominion_name }} (#{{ $spell->cast_by_dominion_realm_number }})</a>
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

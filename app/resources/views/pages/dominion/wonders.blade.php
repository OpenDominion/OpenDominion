@extends('layouts.master')

@section('page-header', 'Wonders of the World')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-pyramids ra-lg"></i> Wonders of the World</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                            <col>
                            <col width="100">
                            <col width="100">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Realm</th>
                                <th>Power</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($wonders as $wonder)
                                <tr>
                                    <td>
                                        {{ $wonder->wonder->name }}
                                    </td>
                                    <td>
                                        @if ($wonder->realm)
                                            #{{ $wonder->realm->number }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        {{ number_format($wonder->power) }}
                                    </td>
                                    <td>
                                        {{ $wonderHelper->getWonderDescription($wonder->wonder) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <form action="{{ route('dominion.wonders') }}" method="post" role="form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-crossed-swords"></i> Attack</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="target_wonder">Select a target</label>
                            <select name="target_wonder" id="target_wonder" class="form-control select2" required style="width: 100%" data-placeholder="Select a target wonder" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                <option></option>
                                @foreach ($wonders as $wonder)
                                    @if ($wonder->realm == null || $selectedDominion->realm->war_realm_id == $wonder->realm->id || $selectedDominion->realm_id == $wonder->realm->war_realm_id)
                                        <option value="{{ $wonder->wonder->id }}" data-war="{{ $wonder->realm !== null ? 1 : 0 }}">
                                            {{ $wonder->wonder->name }}
                                            @if ($wonder->realm !== null)
                                                (#{{ $wonder->realm->number }})
                                            @endif
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Wonders provide bonuses to all dominions in the controlling realm and are acquired by destroying and rebuilding them.</p>
                    <p>All wonders will begin each round in realm 0, with a starting power of 250,000. Once rebuilt, wonder power depends on the damage your realm did to it and time into the round.</p>
                    <p>Each dominion in a realm destroying a wonder that is not in realm 0 receives 100 prestige.</p>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#target_wonder').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
        })(jQuery);

        function select2Template(state) {
            if (!state.id) {
                return state.text;
            }

            const war = state.element.dataset.war;

            warStatus = '';
            if (war == 1) {
                warStatus = '<div class="pull-left">&nbsp;<span class="text-red">WAR</span></div>';
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                ${warStatus}
            `);
        }
    </script>
@endpush

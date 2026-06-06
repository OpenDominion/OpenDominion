@extends('layouts.master')

@section('page-header', 'Restart')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-undo"></i> Restart</span>
                </div>
                <form id="restart-dominion" class="form" action="{{ route('dominion.misc.restart') }}" method="post">
                    @csrf
                    <div class="card-body">
                        <p>You can restart your dominion at any time while still under protection.</p>
                        <div class="mb-3">
                            <label class="form-label">Race:</label>
                            <select name="race" class="form-select">
                                @foreach ($races as $race)
                                    <option value="{{ $race->id }}" data-name="{{ strtolower(str_replace(' ', '', $race->name)) }}" {{ $selectedDominion->race_id == $race->id ? 'selected' : null }}>
                                        {{ $race->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Start Option:</label>
                            <div class="form-check">
                                <input type="radio" id="protection_type_quick" name="protection_type" value="quick" class="form-check-input" checked />
                                <label for="protection_type_quick" class="form-check-label">
                                    Quick Start
                                </label>
                            </div>
                            <div class="form-check">
                                @if ($selectedDominion->round->hasStarted())
                                    <input type="radio" id="protection_type_advanced" name="protection_type" value="advanced" class="form-check-input" disabled />
                                    <label for="protection_type_advanced" class="form-check-label text-muted">
                                        Advanced Simulation (unavailable after round start)
                                    </label>
                                @else
                                    <input type="radio" id="protection_type_advanced" name="protection_type" value="advanced" class="form-check-input" />
                                    <label for="protection_type_advanced" class="form-check-label">
                                        Advanced Simulation
                                    </label>
                                @endif
                            </div>
                            <p>Advanced Simulation requires clicking through the first 48 ticks.</p>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Restart</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-edit"></i> Change Name</span>
                </div>
                <form id="rename-dominion" class="form" action="{{ route('dominion.misc.rename') }}" method="post">
                    @csrf
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Dominion Name:</label>
                            <input name="dominion_name" class="form-control" type="text" placeholder="{{ $selectedDominion->name }}" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ruler Name:</label>
                            <input name="ruler_name" class="form-control" type="text" placeholder="{{ $selectedDominion->ruler_name }}" />
                        </div>
                        @if ($selectedDominion->hero)
                            <div class="mb-3">
                                <label class="form-label">Hero Name:</label>
                                <input name="hero_name" class="form-control" type="text" placeholder="{{ $selectedDominion->hero->name }}" />
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('select[name=race]').change(function() {
                $('input[name=start_option][value=sim]').prop("checked", true).trigger('change');
                $('.race_option').hide();
                var race_key = $(this).find(':selected').data('name');
                $('.race_option[data-race='+race_key+']').show();
            });

            $('input[name=start_option]').change(function() {
                if ($(this).val() == 'sim') {
                    $('.customize_option').addClass('text-muted');
                    $('input[name=customize]').prop("disabled", true);
                } else {
                    $('.customize_option').removeClass('text-muted');
                    $('input[name=customize]').prop("disabled", false);
                }
            });

            $('select[name=race]').trigger('change');
        })(jQuery);
    </script>
@endpush

@extends('layouts.master')

@section('page-header', 'Restart')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-undo"></i> Restart</h3>
                </div>
                <form id="restart-dominion" class="form" action="{{ route('dominion.misc.restart') }}" method="post">
                    @csrf
                    <div class="box-body">
                        <p>You can restart your dominion at any time while still under protection.</p>
                        <p>Standard Simulation requires clicking through the first 72 ticks.</p>
                        <div class="form-group">
                            <label class="form-label">Race:</label>
                            <select name="race" class="form-control">
                                @foreach ($races as $race)
                                    <option value="{{ $race->id }}" data-name="{{ strtolower(str_replace(' ', '', $race->name)) }}" {{ $selectedDominion->race_id == $race->id ? 'selected' : null }}>
                                        {{ $race->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Start Option:</label>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="start_option" value="sim" checked />
                                    Standard Simulation
                                </label>
                            </div>
                            @foreach ($quickstarts as $quickstart)
                                <div class="radio race_option" data-race="{{ $quickstart['race'] }}" style="display: none;">
                                    <label>
                                        <input type="radio" name="start_option" value="{{ $quickstart['filename'] }}" />
                                        Quick Start - {{ ucwords($quickstart['type']) }} - {{ ucwords($quickstart['variant']) }} ({{ $quickstart['size']}} acres)
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-group">
                            <div class="checkbox customize_option text-muted">
                                <label>
                                    <input type="checkbox" name="customize" disabled />
                                    I want to train my own military<br/>
                                    <span class="small">(Start on hour 61 and click through the final 12 ticks)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Restart</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-edit"></i> Change Name</h3>
                </div>
                <form id="rename-dominion" class="form" action="{{ route('dominion.misc.rename') }}" method="post">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label class="form-label">Dominion Name:</label>
                            <input name="dominion_name" class="form-control" type="text" placeholder="{{ $selectedDominion->name }}" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ruler Name:</label>
                            <input name="ruler_name" class="form-control" type="text" placeholder="{{ $selectedDominion->ruler_name }}" />
                        </div>
                    </div>
                    <div class="box-footer">
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

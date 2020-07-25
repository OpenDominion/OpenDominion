@extends('layouts.staff')

@section('page-header', "Dominion: {$dominion->name}")

@section('content')
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">
                Shared logins for: {{ $dominion->name }}
            </h3>
            <select id="dominion-select" class="form-control pull-right">
                <option value="">
                    All
                </option>
                @foreach ($sharedDominions as $sharedDominion)
                    @if ($sharedDominion->id != $dominion->id)
                        <option value="{{ $sharedDominion->id }}" {{ $sharedDominion->id == $selectedDominionId ? 'selected' : null }}>
                            {{ $sharedDominion->name }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="box-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>
                            Dominion
                        </th>
                        <th>
                            Event
                        </th>
                        <th>
                            Ip
                        </th>
                        <th>
                            Device
                        </th>
                        <th>
                            Created
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sharedUserActivity as $activity)
                        @php
                            $sharedDominion == null;
                            if (isset($sharedDominions[$activity->user_id])) {
                                $sharedDominion = $sharedDominions[$activity->user_id];
                            }
                        @endphp
                        @if ($sharedDominion && ($selectedDominionId == -1 || $sharedDominion->id == $dominion->id || ($selectedDominionId == $sharedDominion->id)))
                            <tr>
                                <td>
                                    {{ $sharedDominions[$activity->user_id]->name }}
                                </td>
                                <td>
                                    {{ $activity->key }}
                                </td>
                                <td>
                                    {{ $activity->ip }}
                                </td>
                                <td>
                                    {{ $activity->device }}
                                </td>
                                <td>
                                    {{ $activity->created_at }}
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
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
            $('#dominion-select').select2({ width: '225px' }).change(function() {
                var selectedDominion = $(this).val();

                if(selectedDominion) {
                    window.location.href = "{!! route('staff.moderator.dominion.activity', [$dominion->id]) !!}/?dominion=" + selectedDominion;
                }
                else {
                    window.location.href = "{!! route('staff.moderator.dominion.activity', [$dominion->id]) !!}";
                }
            });

            $('#dominion-select + .select2-container').addClass('pull-right');
        })(jQuery);
    </script>
@endpush

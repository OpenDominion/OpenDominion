@extends('layouts.staff')

@section('page-header', "Dominion: {$dominion->name}")

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Shared logins for: {{ $dominion->name }}
            </h3>
            <select id="dominion-select" class="form-control float-end">
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
        <div class="card-body table-responsive">
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
                                    {{ $sharedDominion->name }}
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
@endpush

@push('page-scripts')
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#dominion-select').select2({ width: '225px' }).change(function() {
                var selectedDominion = $(this).val();

                if (selectedDominion) {
                    window.location.href = "{!! route('staff.moderator.dominion.activity', [$dominion->id]) !!}/?dominion=" + selectedDominion;
                }
                else {
                    window.location.href = "{!! route('staff.moderator.dominion.activity', [$dominion->id]) !!}";
                }
            });

            $('#dominion-select + .select2-container').addClass('float-end');
        })(jQuery);
    </script>
@endpush

@extends('layouts.staff')

@section('page-header', "Dominion: {$dominion->name}")

@section('content')
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">
                {{ $gameEvent->source->name }} (#{{ $gameEvent->source->realm->number }})
                invaded
                {{ $gameEvent->target->name }} (#{{ $gameEvent->target->realm->number }})
                at
                <span>{{ $gameEvent->created_at }}</span>
            </h3>
            <select id="realm-select" class="form-control pull-right">
                <option value="">
                    All
                </option>
                @foreach ($realmNumbers as $realmNumber)
                    <option value="{{ $realmNumber }}" {{ $realmNumber == $selectedRealmNumber ? 'selected' : null }}>
                        #{{ $realmNumber }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="box-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>
                            Performed at
                        </th>
                        <th>
                            Type
                        </th>
                        <th>
                            Performed by
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $markedOlderOps = false;
                    @endphp
                    @foreach ($infoOps as $infoOp)
                        @if(!$markedOlderOps && $infoOp->created_at < $lastDay)
                        @php
                            $markedOlderOps = true;
                        @endphp
                            <tr>
                                <td colspan="3" class="text-center">
                                    Ops taken 24 hours or more before event
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td>
                                <span>{{ $infoOp->created_at }}</span>
                            </td>
                            <td>
                                {{ $infoOp->type }}
                            </td>
                            <td>
                                {{ $infoOp->sourceDominion->name }} (#{{ $infoOp->sourceDominion->realm->number }})
                            </td>
                        </tr>
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
            $('#realm-select').select2({ width: '225px' }).change(function() {
                var selectedRound = $(this).val();

                if(selectedRound) {
                    window.location.href = "{!! route('staff.moderator.dominion.event', [$dominion->id, $gameEvent->id]) !!}/?realm=" + selectedRound;
                }
                else {
                    window.location.href = "{!! route('staff.moderator.dominion.event', [$dominion->id, $gameEvent->id]) !!}";
                }
            });

            $('#realm-select + .select2-container').addClass('pull-right');
        })(jQuery);
    </script>
@endpush

@extends('layouts.staff')

@section('page-header', "User: {$user->display_name}")

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Basic Information</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm mb-0">
                        <colgroup>
                            <col width="150">
                            <col>
                        </colgroup>
                        <tbody>
                            <tr>
                                <th>ID</th>
                                <td>{{ $user->id }}</td>
                            </tr>
                            <tr>
                                <th>Display Name</th>
                                <td>{{ $user->display_name }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $user->email }}</td>
                            </tr>
                            <tr>
                                <th>Last Online</th>
                                <td>
                                    @if ($user->last_online === null)
                                        Never
                                    @else
                                        {{ $user->last_online }}
                                        <span class="text-muted">({{ $user->isOnline() ? 'Online' : $user->last_online->diffForHumans() }})</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created</th>
                                <td>{{ $user->created_at }}</td>
                            </tr>
                            <tr>
                                <th>Updated</th>
                                <td>{{ $user->updated_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Rating</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm small mb-0">
                        <tbody>
                            <tr>
                                <th>Rating</th>
                                <td class="text-end">{{ $user->rating }}</td>
                            </tr>
                            @if (is_array($user->affinities) && !empty($user->affinities))
                                @foreach ($user->affinities as $affinity => $value)
                                    <tr>
                                        <th class="fw-normal">{{ ucfirst($affinity) }} affinity</th>
                                        <td class="text-end">{{ is_numeric($value) ? number_format($value, 2) : json_encode($value) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="2" class="text-muted">No affinities calculated.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Origins</span>
            @if (!$lookupsEnabled)
                <span class="float-end small text-muted">IP lookups unavailable: no IPQS API key configured</span>
            @endif
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>Dominion</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">First Seen</th>
                        <th class="text-center">Last Seen</th>
                        <th class="text-center">Flags</th>
                        <th class="text-center">Fraud Score</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($origins as $origin)
                        <tr>
                            <td>{{ $origin->ip_address }}</td>
                            <td>
                                @if ($origin->dominion)
                                    {{ $origin->dominion->name }}
                                    <span class="text-muted">(Round {{ $origin->dominion->round->number }})</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($origin->count) }}</td>
                            <td class="text-center">{{ $origin->created_at }}</td>
                            <td class="text-center">{{ $origin->updated_at }}</td>
                            @if ($origin->lookup && $origin->lookup->data !== null)
                                <td class="text-center">
                                    @if (!$origin->lookup->hasAnonymizerData())
                                        <span class="text-muted">&mdash;</span>
                                    @else
                                        @include('partials.staff.anonymizer-flags', ['flags' => $origin->lookup->getAnonymizerFlags()])
                                    @endif
                                </td>
                                <td class="text-center">
                                    @include('partials.staff.fraud-score', ['score' => $origin->lookup->score])
                                </td>
                            @else
                                <td class="text-center">
                                    <span class="text-muted">&mdash;</span>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('staff.administrator.users.origin-lookup', $user) }}" method="post" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="ip_address" value="{{ $origin->ip_address }}">
                                        <button type="submit" class="btn btn-sm btn-outline-primary" {{ $lookupsEnabled ? '' : 'disabled' }}>Perform Lookup</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No origins recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <a href="#user-raw-data" class="card-title text-decoration-none" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="user-raw-data">
                <i class="fa fa-caret-right"></i> Raw Data
            </a>
        </div>
        <div class="collapse" id="user-raw-data">
            <div class="card-body">
                <pre class="mb-0">{{ print_r(json_decode($user), true) }}</pre>
            </div>
        </div>
    </div>
@endsection

@extends ('layouts.master')

@section('page-header', 'Valuables')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">

            {{-- Intel for Sale --}}
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-scroll-unfurled"></i> Intel for Sale</span>
                </div>
                <div class="card-body table-responsive">
                    @if ($intelForSale->isEmpty())
                        <p class="text-center text-muted my-3">No intel is currently for sale by your realmmates.</p>
                    @else
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Age</th>
                                    <th>Name</th>
                                    <th>Seller</th>
                                    <th>Spy-Hours</th>
                                    <th>Price</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($intelForSale as $valuable)
                                    @php
                                        $transferPrice = $valuablesHelper->getTransferPrice($valuable);
                                        $insufficient = $selectedDominion->resource_platinum < $transferPrice;
                                        $ageHours = $valuable->discovered_at->diffInHours(now());
                                        $ageClass = $ageHours >= 48 ? 'text-danger' : ($ageHours >= 24 ? 'text-warning' : '');
                                        // Floored against your own size; only written to the database on purchase
                                        $effectiveSpyHours = $valuablesHelper->getEffectiveRequiredSpyHours($valuable, $selectedDominion);
                                        $isFloored = $effectiveSpyHours > $valuable->required_spy_hours;
                                        $minSpiesNeeded = (int) ceil($effectiveSpyHours / \OpenDominion\Helpers\ValuablesHelper::MIN_INVESTIGATION_HOURS);
                                        $notEnoughSpies = $availableSpies < $minSpiesNeeded;
                                    @endphp
                                    <tr>
                                        <td class="{{ $ageClass }}">{{ $valuable->discovered_at->diffForHumans() }}</td>
                                        <td>
                                            <strong class="{{ $valuablesHelper->getRarityClass($valuable->rarity) }}">{{ $valuable->name }}</strong><br>
                                            <small class="text-muted">{{ ucfirst($valuable->rarity) }} &middot; {{ ucfirst($valuable->type) }}</small>
                                        </td>
                                        <td>{{ $valuable->sourceDominion->name }}</td>
                                        <td class="{{ $notEnoughSpies ? 'text-danger' : '' }}">
                                            @if ($notEnoughSpies)
                                                <span data-bs-toggle="tooltip" title="You need at least {{ number_format($minSpiesNeeded) }} spies">{{ number_format($effectiveSpyHours) }}</span>
                                            @else
                                                {{ number_format($effectiveSpyHours) }}
                                            @endif
                                            @if ($isFloored)
                                                <i class="fa fa-info-circle text-muted"
                                                   data-bs-toggle="tooltip"
                                                   title="Raised from {{ number_format($valuable->required_spy_hours) }}. A heist costs at least {{ number_format(\OpenDominion\Helpers\ValuablesHelper::MIN_SPY_HOURS_LAND_RATIO * 100) }}% of your own land, scaled by rarity."></i>
                                            @endif
                                        </td>
                                        <td>{{ number_format($transferPrice) }}p</td>
                                        <td class="text-end">
                                            <form action="{{ route('dominion.valuables.purchase', $valuable->id) }}" method="post" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" {{ $insufficient ? 'disabled' : '' }} title="{{ $insufficient ? 'Not enough platinum' : '' }}">
                                                    Buy Intel
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            {{-- Your Discoveries --}}
            <div class="card card-primary mt-3">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-locked-chest"></i> Your Discoveries</span>
                </div>
                <div class="card-body table-responsive">
                    @if ($valuablesDiscovered->isEmpty())
                        <p class="text-center text-muted my-3">No valuables discovered.</p>
                    @else
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Discovered</th>
                                    <th>Name</th>
                                    <th>Target</th>
                                    <th>Spies</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($valuablesDiscovered as $valuable)
                                    <tr>
                                        <td>{{ $valuable->discovered_at->diffForHumans() }}</td>
                                        <td>
                                            <strong class="{{ $valuablesHelper->getRarityClass($valuable->rarity) }}">{{ $valuable->name }}</strong><br>
                                            <small class="text-muted">{{ ucfirst($valuable->rarity) }} &middot; {{ ucfirst($valuable->type) }}</small>
                                        </td>
                                        <td>{{ $valuable->targetDominion->name }}</td>
                                        <td>{{ $valuable->spies_assigned ? number_format($valuable->spies_assigned) : '—' }}</td>
                                        <td>
                                            @if ($valuable->status === \OpenDominion\Models\Valuable::STATUS_INVESTIGATING)
                                                @php
                                                    $pct = $valuablesHelper->getInvestigationProgress($valuable);
                                                    $remaining = max(0, now()->diffInHours($valuable->investigation_ends_at, false));
                                                @endphp
                                                <span class="{{ $valuablesHelper->getInvestigationProgressColorClass($pct) }}">{{ number_format($pct, 0) }}%</span>
                                                <small class="text-muted">({{ ceil($remaining) }} ticks left)</small>
                                            @elseif ($valuable->status === \OpenDominion\Models\Valuable::STATUS_LISTED_FOR_TRANSFER || $valuable->is_listed)
                                                <span class="text-info">Listed for sale</span>
                                            @else
                                                <span class="text-muted">Ready</span>
                                                <small class="text-muted d-block">Intel worth {{ number_format($valuablesHelper->getTransferPrice($valuable)) }}p</small>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($valuable->status === \OpenDominion\Models\Valuable::STATUS_INVESTIGATING)
                                                <form action="{{ route('dominion.valuables.cancel', $valuable->id) }}" method="post" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                                </form>
                                            @elseif ($valuable->is_listed)
                                                <form action="{{ route('dominion.valuables.unlist', $valuable->id) }}" method="post" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-secondary">Unlist</button>
                                                </form>
                                            @else
                                                @if (!$valuable->transferred)
                                                    <form action="{{ route('dominion.valuables.list', $valuable->id) }}" method="post" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">Sell Intel</button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('dominion.valuables.investigate', $valuable->id) }}" class="btn btn-sm btn-primary">Investigate</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            {{-- Stolen Goods --}}
            <div class="card card-primary mt-3">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-open-chest"></i> Stolen Goods</span>
                </div>
                <div class="card-body table-responsive">
                    @if ($valuablesStolen->isEmpty())
                        <p class="text-center text-muted my-3">No stolen valuables awaiting sale.</p>
                    @else
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Stolen</th>
                                    <th>Name</th>
                                    <th>Target</th>
                                    <th>Price</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($valuablesStolen as $valuable)
                                    <tr>
                                        <td>{{ $valuable->stolen_at->diffForHumans() }}</td>
                                        <td>
                                            <strong class="{{ $valuablesHelper->getRarityClass($valuable->rarity) }}">{{ $valuable->name }}</strong><br>
                                            <small class="text-muted">{{ ucfirst($valuable->rarity) }} &middot; {{ ucfirst($valuable->type) }}</small>
                                        </td>
                                        <td>{{ $valuable->targetDominion->name }}</td>
                                        @php
                                            $graceHoursLeft = $valuablesHelper->getRemainingSalePriceGraceHours($valuable);
                                        @endphp
                                        <td>
                                            {{ number_format($valuablesHelper->getCurrentSalePrice($valuable)) }}p
                                            @if ($graceHoursLeft > 0)
                                                <span class="badge text-bg-success"
                                                      data-bs-toggle="tooltip"
                                                      title="Full price for another {{ $graceHoursLeft }} {{ str_plural('hour', $graceHoursLeft) }} before it starts to decay.">
                                                    Full price
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <form action="{{ route('dominion.valuables.sell', $valuable->id) }}" method="post" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">Sell</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">How Valuables Work</span>
                </div>
                <div class="card-body">
                    <p>Info ops occasionally discover intel about valuables held by targets. Use <strong>Investigate</strong> to commit your spies for several hours to steal the valuable.</p>
                    <p><strong>Sell</strong> lets you fence stolen goods on the black market for platinum.</p>
                    <p><strong>Sell Intel</strong> shares your discovery with a realmmate for a finder's fee &mdash; they run the heist with their own spies.</p>
                    <p class="text-warning">Discoveries older than 48 hours become very risky to act on.</p>
                    <hr>
                    <p>Available spies: <strong>{{ number_format($availableSpies) }}</strong></p>
                    <p>Spy strength: <strong>{{ sprintf("%.4g", $spyStrength) }}%</strong></p>
                    <p>Active investigations: <strong>{{ $activeInvestigations }}</strong></p>
                    <p><a href="{{ route('dominion.valuables.history') }}">View round history &raquo;</a></p>
                </div>
            </div>
        </div>
    </div>
@endsection

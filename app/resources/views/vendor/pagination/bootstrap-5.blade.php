@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $window = (int) ($paginator->onEachSide ?? 2);
    $windowStart = max(2, $current - $window);
    $windowEnd = min($last - 1, $current + $window);
@endphp

@if ($paginator->hasPages())
    <nav>
        <ul class="pagination mb-0">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            {{-- First page (always visible) --}}
            @if ($current == 1)
                <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
            @else
                <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}">1</a></li>
            @endif

            {{-- Left ellipsis --}}
            @if ($windowStart > 2)
                <li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>
            @elseif ($current > 2)
                {{-- Mobile-only: window pages between 1 and current are hidden on mobile, so show ellipsis to indicate gap --}}
                <li class="page-item disabled d-sm-none" aria-disabled="true"><span class="page-link">...</span></li>
            @endif

            {{-- Middle window: current visible everywhere, others desktop-only --}}
            @for ($i = $windowStart; $i <= $windowEnd; $i++)
                @if ($i == $current)
                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $i }}</span></li>
                @else
                    <li class="page-item d-none d-sm-block"><a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a></li>
                @endif
            @endfor

            {{-- Right ellipsis --}}
            @if ($windowEnd < $last - 1)
                <li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>
            @elseif ($current < $last - 1)
                {{-- Mobile-only: window pages between current and last are hidden on mobile, so show ellipsis to indicate gap --}}
                <li class="page-item disabled d-sm-none" aria-disabled="true"><span class="page-link">...</span></li>
            @endif

            {{-- Last page (always visible, only if more than 1 page) --}}
            @if ($last > 1)
                @if ($current == $last)
                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $last }}</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($last) }}">{{ $last }}</a></li>
                @endif
            @endif

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif

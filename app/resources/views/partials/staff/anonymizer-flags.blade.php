{{-- Expects $flags: array of ['label' => string, 'severity' => string], most severe first --}}
@forelse ($flags as $flag)
    <span class="badge text-bg-{{ $flag['severity'] }}">{{ $flag['label'] }}</span>
@empty
    <span class="badge text-bg-success">Clean</span>
@endforelse

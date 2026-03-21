@php
    $class = match ($status) {
        'Open' => 'badge badge-open',
        'In Progress' => 'badge badge-progress',
        'Resolved' => 'badge badge-resolved',
        'Closed' => 'badge badge-closed',
        default => 'badge',
    };
@endphp

<span class="{{ $class }}">{{ $status }}</span>

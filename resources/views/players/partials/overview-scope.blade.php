@php
    $kind = $kind ?? 'all';
    $scopeLabel = match ($kind) {
        'recent' => '30 dni',
        'form' => '3 miesiące',
        default => 'Cała kariera',
    };
@endphp
<span class="overview-scope overview-scope--{{ $kind }}">{{ $scopeLabel }}</span>

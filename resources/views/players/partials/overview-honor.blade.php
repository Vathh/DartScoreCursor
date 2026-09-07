@php
    $tone = $tone ?? 'gold';
    $count = (int) ($count ?? 0);
    $label = $label ?? '';
    $rank = match ($tone) {
        'silver' => '2',
        'bronze' => '3',
        default => '1',
    };
@endphp
<div class="overview-honor overview-honor--{{ $tone }} {{ $count === 0 ? 'is-empty' : '' }}">
    <span class="overview-honor-icon" aria-hidden="true">
        @if($tone === 'title')
            <svg viewBox="0 0 32 32" fill="none">
                <path d="M8 6h16v6c0 4.4-3.6 8-8 8s-8-3.6-8-8V6z" fill="#f59e0b"/>
                <path d="M10 8h12v4c0 3.3-2.7 6-6 6s-6-2.7-6-6V8z" fill="#fbbf24"/>
                <path d="M6 7h4v5c-2.2 0-4-1.3-4-3.2V7z" fill="#d97706"/>
                <path d="M22 7h4v1.8c0 1.9-1.8 3.2-4 3.2V7z" fill="#d97706"/>
                <path d="M15 20h2v4h-2z" fill="#b45309"/>
                <path d="M11 24h10v2.5a1 1 0 0 1-1 1h-8a1 1 0 0 1-1-1V24z" fill="#f59e0b"/>
                <path d="M10 27.5h12V29H10z" fill="#b45309"/>
            </svg>
        @else
            <svg viewBox="0 0 32 32" fill="none">
                <path d="M10 2.5h5.5L14 11H7.5L10 2.5z" fill="#9f1239"/>
                <path d="M16.5 2.5H22L24.5 11H18L16.5 2.5z" fill="#be123c"/>
                @if($tone === 'gold')
                    <circle cx="16" cy="20.5" r="9" fill="#d97706"/>
                    <circle cx="16" cy="20.5" r="7.2" fill="#fbbf24"/>
                    <circle cx="13.6" cy="18.2" r="2.2" fill="#fde68a" opacity=".55"/>
                    <text x="16" y="20.6" text-anchor="middle" dominant-baseline="central" font-size="10" font-weight="700" font-family="ui-sans-serif, system-ui, sans-serif" fill="#78350f">{{ $rank }}</text>
                @elseif($tone === 'silver')
                    <circle cx="16" cy="20.5" r="9" fill="#64748b"/>
                    <circle cx="16" cy="20.5" r="7.2" fill="#cbd5e1"/>
                    <circle cx="13.6" cy="18.2" r="2.2" fill="#f8fafc" opacity=".55"/>
                    <text x="16" y="20.6" text-anchor="middle" dominant-baseline="central" font-size="10" font-weight="700" font-family="ui-sans-serif, system-ui, sans-serif" fill="#334155">{{ $rank }}</text>
                @else
                    <circle cx="16" cy="20.5" r="9" fill="#9a3412"/>
                    <circle cx="16" cy="20.5" r="7.2" fill="#d97706"/>
                    <circle cx="13.6" cy="18.2" r="2.2" fill="#fde68a" opacity=".4"/>
                    <text x="16" y="20.6" text-anchor="middle" dominant-baseline="central" font-size="10" font-weight="700" font-family="ui-sans-serif, system-ui, sans-serif" fill="#431407">{{ $rank }}</text>
                @endif
            </svg>
        @endif
    </span>
    <span>
        <span class="overview-honor-value">{{ $count }}</span>
        <span class="overview-honor-label">{{ $label }}</span>
    </span>
</div>

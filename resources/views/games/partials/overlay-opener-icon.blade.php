<span
    class="game-overlay-opener"
    title="Otwiera"
    aria-label="Otwiera"
    x-show="isLegOpener({{ (int) $index }})"
    @if(! ($overlayPreview && (int) $index === 0)) x-cloak @endif
>
    <svg viewBox="0 0 28 16" fill="currentColor" aria-hidden="true">
        <path d="M1.1 3.1 8.4 8 1.1 12.9 3.6 8z" opacity=".55"/>
        <path d="M5.2 3.1 12.5 8 5.2 12.9 7.7 8z"/>
        <path d="M11.4 7.25h8.2v1.5h-8.2z"/>
        <path d="M18.8 6.15h4.5l.7 1.85-.7 1.85h-4.5z"/>
        <path d="M23.3 8h4.4L23.5 6.2v3.6z"/>
    </svg>
</span>

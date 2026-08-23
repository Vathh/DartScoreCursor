@extends('layouts.app')

@section('title', 'Piaskownica — koło checkoutów')

@section('content')
    <div class="cw-sandbox py-4 sm:py-6" x-data="checkoutWheelSandbox">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-accent mb-2">Sandbox</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-text">Koło checkoutów</h1>
            <p class="text-text-secondary mt-2 max-w-2xl">
                Bronze i Gold — czysty fill + ciepły bloom, bez obcej kreski w środku.
                Apex to rozżarzone złoto (ciało jasne, żar złoty), nie czarny kafelek z białym halo.
            </p>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] items-start">
            <div class="space-y-4">
                <div class="card !p-0 overflow-visible" :style="{ background: bg }">
                    <div class="checkout-wheel-host px-4 py-8 sm:px-8" x-ref="host" :style="hostBoxShadowStyle()">
                        {!! $svg !!}
                    </div>
                </div>
                <details class="card">
                    <summary class="cursor-pointer text-sm font-semibold text-text">Referencja PNG (kolory / glow)</summary>
                    <img
                        src="{{ asset('images/checkout wheel.png') }}"
                        alt="Docelowy wygląd koła checkoutów"
                        class="mt-3 w-full rounded-lg border border-border"
                    >
                </details>
            </div>

            <aside class="cw-controls card space-y-5 xl:sticky xl:top-4 xl:max-h-[calc(100dvh-2rem)] xl:overflow-y-auto xl:overscroll-contain">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-text-muted mb-2">Zaznaczenie</p>
                    <p class="text-text font-semibold" x-text="selected ? ('Checkout ' + selected) : 'Kliknij segment na kole'"></p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-text-muted mb-2">Poziomy — neon / żar</p>
                    <p class="text-xs text-text-muted mb-3">Miedź → amber → żółć → stopione złoto. Inner stroke dopiero od Bright, gdy fill jest jasny.</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="btn btn-secondary !py-1.5 !px-3 text-sm" @click="usePreset('locked')">Locked</button>
                        <button type="button" class="btn btn-secondary !py-1.5 !px-3 text-sm" @click="usePreset('iron')">Iron</button>
                        <button type="button" class="btn btn-secondary !py-1.5 !px-3 text-sm" @click="usePreset('bronze')">Bronze</button>
                        <button type="button" class="btn btn-secondary !py-1.5 !px-3 text-sm" @click="usePreset('gold')">Gold</button>
                        <button type="button" class="btn btn-secondary !py-1.5 !px-3 text-sm" @click="usePreset('bright')">Bright</button>
                        <button type="button" class="btn btn-secondary !py-1.5 !px-3 text-sm" @click="usePreset('apex')">Apex</button>
                    </div>
                </div>

                <label class="block">
                    <span class="cw-label">Tło karty</span>
                    <div class="cw-color-row">
                        <input type="color" x-model="bg">
                        <input type="text" class="cw-input" x-model="bg">
                    </div>
                </label>

                <div class="border-t border-border pt-4 space-y-3" x-effect="onBrushChange()">
                    <p class="text-xs font-semibold uppercase tracking-wide text-accent">Wypełnienie</p>
                    <label class="block">
                        <span class="cw-label">Kolor</span>
                        <div class="cw-color-row">
                            <input type="color" x-model="brush.fill">
                            <input type="text" class="cw-input" x-model="brush.fill">
                        </div>
                    </label>
                    <label class="block">
                        <span class="cw-label">Tekst</span>
                        <div class="cw-color-row">
                            <input type="color" x-model="brush.label">
                            <input type="text" class="cw-input" x-model="brush.label">
                        </div>
                    </label>
                </div>

                <div class="border-t border-border pt-4 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-accent">Kontur zewnętrzny (szczelina)</p>
                    <label class="block">
                        <span class="cw-label">Stroke</span>
                        <div class="cw-color-row">
                            <input type="color" x-model="brush.stroke">
                            <input type="text" class="cw-input" x-model="brush.stroke">
                        </div>
                    </label>
                    <label class="block">
                        <span class="cw-label">Grubość <span class="text-text-muted" x-text="brush.strokeWidth + ' px'"></span></span>
                        <input type="range" min="0" max="8" step="0.25" x-model.number="brush.strokeWidth" class="cw-range">
                    </label>
                </div>

                <div class="border-t border-border pt-4 space-y-3">
                    <label class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-accent">Inner stroke (~1px w PNG)</span>
                        <input type="checkbox" x-model="brush.innerStroke.enabled">
                    </label>
                    <p class="text-xs text-text-muted">Druga kopia path + clip — tylko wewnętrzna połowa kreski, nie box.</p>
                    <label class="block">
                        <span class="cw-label">Kolor</span>
                        <div class="cw-color-row">
                            <input type="color" x-model="brush.innerStroke.color">
                            <input type="text" class="cw-input" x-model="brush.innerStroke.color">
                        </div>
                    </label>
                    <label class="block">
                        <span class="cw-label">Szerokość wewnątrz <span class="text-text-muted" x-text="brush.innerStroke.width + ' px'"></span></span>
                        <input type="range" min="0.25" max="4" step="0.25" x-model.number="brush.innerStroke.width" class="cw-range">
                    </label>
                </div>

                <div class="border-t border-border pt-4 space-y-3">
                    <label class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-accent">Bloom (outer glow)</span>
                        <input type="checkbox" x-model="brush.outerGlow.enabled">
                    </label>
                    <p class="text-xs text-text-muted">CSS <code class="text-text-secondary">drop-shadow</code> po obrysie klina — wylewa się na sąsiadów.</p>
                    <label class="block">
                        <span class="cw-label">Kolor</span>
                        <div class="cw-color-row">
                            <input type="color" x-model="brush.outerGlow.color">
                            <input type="text" class="cw-input" x-model="brush.outerGlow.color">
                        </div>
                    </label>
                    <label class="block">
                        <span class="cw-label">Blur <span class="text-text-muted" x-text="brush.outerGlow.blur + ' px'"></span></span>
                        <input type="range" min="0" max="40" step="1" x-model.number="brush.outerGlow.blur" class="cw-range">
                    </label>
                    <label class="block">
                        <span class="cw-label">Opacity <span class="text-text-muted" x-text="brush.outerGlow.opacity"></span></span>
                        <input type="range" min="0" max="1" step="0.05" x-model.number="brush.outerGlow.opacity" class="cw-range">
                    </label>
                    <label class="flex items-center gap-2 text-sm text-text-secondary">
                        <input type="checkbox" x-model="brush.glowOnLabel">
                        Glow także na liczbach
                    </label>
                </div>

                <div class="border-t border-border pt-4 space-y-3">
                    <label class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-accent">Wewnętrzny glow (extra)</span>
                        <input type="checkbox" x-model="brush.innerGlow.enabled">
                    </label>
                    <p class="text-xs text-text-muted">Miękka poświata <em>w</em> klinie, pod rimem — daje grubość, nie rozjaśnia całego pola.</p>
                    <label class="block">
                        <span class="cw-label">Kolor</span>
                        <div class="cw-color-row">
                            <input type="color" x-model="brush.innerGlow.color">
                            <input type="text" class="cw-input" x-model="brush.innerGlow.color">
                        </div>
                    </label>
                    <label class="block">
                        <span class="cw-label">Blur <span class="text-text-muted" x-text="brush.innerGlow.blur"></span></span>
                        <input type="range" min="0" max="12" step="0.5" x-model.number="brush.innerGlow.blur" class="cw-range">
                    </label>
                    <label class="block">
                        <span class="cw-label">Erozja <span class="text-text-muted" x-text="brush.innerGlow.erode"></span></span>
                        <input type="range" min="0" max="8" step="0.25" x-model.number="brush.innerGlow.erode" class="cw-range">
                    </label>
                    <label class="block">
                        <span class="cw-label">Opacity <span class="text-text-muted" x-text="brush.innerGlow.opacity"></span></span>
                        <input type="range" min="0" max="1" step="0.05" x-model.number="brush.innerGlow.opacity" class="cw-range">
                    </label>
                </div>

                <div class="border-t border-border pt-4 space-y-3">
                    <label class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-accent">Box-shadow całego koła</span>
                        <input type="checkbox" x-model="hostShadow.enabled">
                    </label>
                    <p class="text-xs text-text-muted">Prostokątna aura wokół SVG — w PNG tego nie ma, zostawiam do testów.</p>
                    <label class="block">
                        <span class="cw-label">Kolor</span>
                        <div class="cw-color-row">
                            <input type="color" x-model="hostShadow.color">
                            <input type="text" class="cw-input" x-model="hostShadow.color">
                        </div>
                    </label>
                    <label class="block">
                        <span class="cw-label">Blur <span class="text-text-muted" x-text="hostShadow.blur + ' px'"></span></span>
                        <input type="range" min="0" max="80" step="1" x-model.number="hostShadow.blur" class="cw-range">
                    </label>
                    <label class="block">
                        <span class="cw-label">Spread <span class="text-text-muted" x-text="hostShadow.spread + ' px'"></span></span>
                        <input type="range" min="-20" max="40" step="1" x-model.number="hostShadow.spread" class="cw-range">
                    </label>
                    <label class="block">
                        <span class="cw-label">Opacity <span class="text-text-muted" x-text="hostShadow.opacity"></span></span>
                        <input type="range" min="0" max="1" step="0.05" x-model.number="hostShadow.opacity" class="cw-range">
                    </label>
                </div>

                <div class="flex flex-col gap-2">
                    <button type="button" class="btn btn-secondary !py-2 text-sm" @click="paintDemoMix()">Wgraj demo wszystkich poziomów</button>
                    <button type="button" class="btn btn-secondary !py-2 text-sm" @click="resetAll()">Reset do Locked</button>
                </div>
                <div class="cw-controls-end" aria-hidden="true"></div>
            </aside>
        </div>
    </div>
@endsection

@section('scripts')
<style>
    .cw-sandbox .cw-label {
        display: block;
        margin-bottom: 0.35rem;
        font-size: 0.75rem;
        color: var(--color-text-muted);
    }
    .cw-sandbox .cw-input {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid var(--color-border);
        background: var(--color-bg);
        color: var(--color-text);
        font-size: 0.8rem;
        padding: 0.35rem 0.55rem;
    }
    .cw-sandbox .cw-color-row {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    .cw-sandbox .cw-color-row input[type="color"] {
        width: 2.4rem;
        height: 2rem;
        padding: 0;
        border: 1px solid var(--color-border);
        border-radius: 0.4rem;
        background: transparent;
        cursor: pointer;
    }
    .cw-sandbox .cw-range {
        width: 100%;
        accent-color: var(--color-accent);
    }
    @media (min-width: 1280px) {
        .cw-sandbox .cw-controls {
            padding-bottom: 50vh;
        }
        .cw-sandbox .cw-controls-end {
            height: 40vh;
        }
        .cw-sandbox .cw-controls::-webkit-scrollbar {
            width: 8px;
        }
        .cw-sandbox .cw-controls::-webkit-scrollbar-thumb {
            background: var(--color-border);
            border-radius: 999px;
        }
    }
    .cw-sandbox .checkout-wheel-host {
        background: radial-gradient(circle at 50% 48%, rgba(245, 158, 11, 0.10) 0%, rgba(12, 12, 15, 0) 58%);
    }
    .cw-sandbox .checkout-wheel-host svg {
        display: block;
        width: min(720px, 100%);
        height: auto;
        margin-inline: auto;
        overflow: visible;
    }
    .cw-sandbox [data-checkout] {
        cursor: pointer;
    }
    .cw-sandbox [data-checkout] .checkout-segment,
    .cw-sandbox [data-checkout] circle.checkout-shape {
        fill: var(--cw-fill, #25252d);
        stroke: var(--cw-stroke, #2e2e38);
        stroke-width: var(--cw-stroke-width, 1px);
        stroke-linejoin: round;
    }
    .cw-sandbox [data-checkout] .checkout-shape {
        filter: var(--cw-shape-filter, none);
    }
    .cw-sandbox [data-checkout] {
        filter: var(--cw-group-filter, none);
    }
    .cw-sandbox [data-checkout] .checkout-halo {
        pointer-events: none;
        stroke: none !important;
    }
    .cw-sandbox [data-checkout] .checkout-inner-stroke {
        fill: none !important;
        pointer-events: none;
        filter: none;
        stroke-linejoin: round;
    }
    .cw-sandbox [data-checkout] .checkout-label {
        fill: var(--cw-label, #f4f4f5);
        pointer-events: none;
    }
    .cw-sandbox [data-checkout].is-selected .checkout-label {
        filter: drop-shadow(0 0 5px rgba(56, 189, 248, 0.95));
    }
</style>
<script>
document.addEventListener('alpine:init', () => {
    const offGlow = { enabled: false, color: '#2e2e38', blur: 0, opacity: 0 };
    const offInnerGlow = { enabled: false, color: '#2e2e38', blur: 0, opacity: 0, erode: 0 };
    const offInnerStroke = { enabled: false, color: '#f4f4f5', width: 1 };

    Alpine.data('checkoutWheelSandbox', () => ({
        selected: null,
        bg: '#0c0c0f',
        svgNs: 'http://www.w3.org/2000/svg',
        brush: null,
        hostShadow: {
            enabled: false,
            color: '#f59e0b',
            blur: 48,
            spread: 0,
            opacity: 0.28,
        },
        presets: {
            locked: {
                fill: '#16161a',
                stroke: '#0c0c0f',
                strokeWidth: 1.25,
                label: '#52525b',
                glowOnLabel: false,
                innerStroke: { ...offInnerStroke },
                outerGlow: { ...offGlow },
                innerGlow: { ...offInnerGlow },
            },
            iron: {
                fill: '#27272a',
                stroke: '#0c0c0f',
                strokeWidth: 1.25,
                label: '#e4e4e7',
                glowOnLabel: false,
                innerStroke: { enabled: true, color: '#a1a1aa', width: 0.85 },
                outerGlow: { ...offGlow },
                innerGlow: { ...offInnerGlow },
            },
            bronze: {
                fill: '#7c2d12',
                stroke: '#0c0c0f',
                strokeWidth: 1.25,
                label: '#fed7aa',
                glowOnLabel: false,
                innerStroke: { ...offInnerStroke },
                outerGlow: { enabled: true, color: '#c2410c', blur: 6, opacity: 0.28 },
                innerGlow: { ...offInnerGlow },
            },
            gold: {
                fill: '#d97706',
                stroke: '#0c0c0f',
                strokeWidth: 1.25,
                label: '#fffbeb',
                glowOnLabel: false,
                innerStroke: { ...offInnerStroke },
                outerGlow: { enabled: true, color: '#f59e0b', blur: 9, opacity: 0.4 },
                innerGlow: { ...offInnerGlow },
            },
            bright: {
                fill: '#eab308',
                stroke: '#0c0c0f',
                strokeWidth: 1.25,
                label: '#141418',
                glowOnLabel: false,
                innerStroke: { enabled: true, color: '#fef08a', width: 1 },
                outerGlow: { enabled: true, color: '#facc15', blur: 11, opacity: 0.45 },
                innerGlow: { enabled: true, color: '#fde68a', blur: 2.5, opacity: 0.35, erode: 1.5 },
            },
            apex: {
                fill: '#fde047',
                stroke: '#0c0c0f',
                strokeWidth: 1.25,
                label: '#141418',
                glowOnLabel: false,
                innerStroke: { ...offInnerStroke },
                outerGlow: { enabled: true, color: '#f59e0b', blur: 28, opacity: 0.7 },
                innerGlow: { enabled: true, color: '#fffbeb', blur: 5, opacity: 0.45, erode: 3 },
            },
        },
        init() {
            this.brush = this.cloneBrush(this.presets.gold);
            const svg = this.$refs.host.querySelector('svg');
            if (!svg) return;

            svg.removeAttribute('width');
            svg.removeAttribute('height');
            svg.setAttribute('overflow', 'visible');

            if (!svg.querySelector('defs')) {
                svg.prepend(this.svgEl('defs'));
            }

            const wheel = svg.querySelector('#checkout-wheel');
            const center = svg.querySelector('#checkout-170');
            if (wheel && center) {
                wheel.appendChild(center);
            }

            svg.querySelectorAll('[data-checkout]').forEach((group) => {
                group.setAttribute('overflow', 'visible');
                group.querySelectorAll('path, circle').forEach((shape) => {
                    if (shape.classList.contains('checkout-halo') || shape.classList.contains('checkout-inner-stroke')) {
                        return;
                    }
                    shape.removeAttribute('style');
                    shape.removeAttribute('fill');
                    shape.removeAttribute('stroke');
                    shape.removeAttribute('stroke-width');
                    shape.classList.add('checkout-shape');
                });
                group.querySelectorAll('text').forEach((label) => {
                    label.removeAttribute('fill');
                });
                group.addEventListener('click', () => this.onSegmentClick(group));
                this.applyBrushToGroup(group, this.presets.locked);
            });

            this.paintDemoMix();
        },
        svgEl(name, attrs = {}) {
            const el = document.createElementNS(this.svgNs, name);
            Object.entries(attrs).forEach(([key, value]) => el.setAttribute(key, String(value)));
            return el;
        },
        hexA(hex, opacity) {
            const raw = hex.replace('#', '');
            const full = raw.length === 3
                ? raw.split('').map((ch) => ch + ch).join('')
                : raw;
            const n = parseInt(full.slice(0, 6), 16);
            const r = (n >> 16) & 255;
            const g = (n >> 8) & 255;
            const b = n & 255;
            return `rgba(${r}, ${g}, ${b}, ${opacity})`;
        },
        hostBoxShadowStyle() {
            if (!this.hostShadow.enabled) {
                return { boxShadow: 'none' };
            }
            const color = this.hexA(this.hostShadow.color, this.hostShadow.opacity);
            return {
                boxShadow: `0 0 ${this.hostShadow.blur}px ${this.hostShadow.spread}px ${color}`,
            };
        },
        cloneBrush(source) {
            return JSON.parse(JSON.stringify(source));
        },
        usePreset(name) {
            this.brush = this.cloneBrush(this.presets[name]);
            if (this.selected) {
                this.repaintSelected();
            }
        },
        onBrushChange() {
            if (!this.brush) return;
            void this.brush.fill;
            void this.brush.stroke;
            void this.brush.strokeWidth;
            void this.brush.label;
            void this.brush.glowOnLabel;
            void this.brush.innerStroke.enabled;
            void this.brush.innerStroke.color;
            void this.brush.innerStroke.width;
            void this.brush.outerGlow.enabled;
            void this.brush.outerGlow.color;
            void this.brush.outerGlow.blur;
            void this.brush.outerGlow.opacity;
            void this.brush.innerGlow.enabled;
            void this.brush.innerGlow.color;
            void this.brush.innerGlow.blur;
            void this.brush.innerGlow.opacity;
            void this.brush.innerGlow.erode;
            this.repaintSelected();
        },
        onSegmentClick(group) {
            this.$refs.host.querySelectorAll('[data-checkout].is-selected').forEach((el) => {
                el.classList.remove('is-selected');
            });
            group.classList.add('is-selected');
            this.selected = group.getAttribute('data-checkout');
            this.applyBrushToGroup(group, this.brush);
        },
        repaintSelected() {
            if (!this.selected) return;
            const group = this.$refs.host.querySelector(`[data-checkout="${this.selected}"]`);
            if (group) {
                this.applyBrushToGroup(group, this.brush);
            }
        },
        resetAll() {
            this.$refs.host.querySelectorAll('[data-checkout]').forEach((group) => {
                group.classList.remove('is-selected');
                this.applyBrushToGroup(group, this.presets.locked);
            });
            this.selected = null;
            this.brush = this.cloneBrush(this.presets.gold);
        },
        paintDemoMix() {
            const map = {
                170: 'apex',
                167: 'gold',
                164: 'bright',
                161: 'bronze',
                151: 'iron',
                152: 'bronze',
                154: 'gold',
                157: 'bright',
                160: 'apex',
                138: 'iron',
                140: 'bronze',
                144: 'gold',
                148: 'bright',
                150: 'apex',
                121: 'iron',
                126: 'bronze',
                130: 'gold',
                134: 'bright',
                100: 'iron',
                107: 'bronze',
                112: 'gold',
                118: 'apex',
                120: 'bright',
            };
            Object.entries(map).forEach(([checkout, preset]) => {
                const group = this.$refs.host.querySelector(`[data-checkout="${checkout}"]`);
                if (group) {
                    this.applyBrushToGroup(group, this.presets[preset]);
                }
            });
        },
        applyBrushToGroup(group, brush) {
            group.style.setProperty('--cw-fill', brush.fill);
            group.style.setProperty('--cw-stroke', brush.stroke);
            group.style.setProperty('--cw-stroke-width', `${brush.strokeWidth}px`);
            group.style.setProperty('--cw-label', brush.label);

            this.syncHalo(group, brush);
            this.syncInnerStroke(group, brush);

            const innerId = brush.innerGlow.enabled
                ? this.syncInnerGlowFilter(group, brush.innerGlow)
                : null;

            group.style.setProperty('--cw-shape-filter', innerId ? `url(#${innerId})` : 'none');
            group.style.setProperty('--cw-group-filter', 'none');
        },
        syncHalo(group, brush) {
            const svg = this.$refs.host.querySelector('svg');
            const defs = svg.querySelector('defs');
            const checkout = group.getAttribute('data-checkout');
            const shape = group.querySelector('.checkout-shape');

            group.querySelectorAll('.checkout-halo').forEach((el) => el.remove());
            svg.querySelectorAll(`[data-halo-for="${checkout}"]`).forEach((el) => el.remove());
            [0, 1].forEach((i) => document.getElementById(`cw-halo-filter-${checkout}-${i}`)?.remove());
            svg.querySelector('#cw-halo-layer')?.remove();

            if (!shape || !brush.outerGlow.enabled || brush.outerGlow.blur <= 0) {
                return;
            }

            const glow = brush.outerGlow;
            const layers = [
                { blur: Math.max(8, glow.blur * 0.9), opacity: glow.opacity * 0.55, color: glow.color },
                { blur: Math.max(4, glow.blur * 0.4), opacity: Math.min(0.7, glow.opacity * 0.75), color: brush.fill },
            ];

            layers.forEach((spec, index) => {
                const filterId = `cw-halo-filter-${checkout}-${index}`;
                const filter = this.svgEl('filter', {
                    id: filterId,
                    x: '-150%',
                    y: '-150%',
                    width: '400%',
                    height: '400%',
                    filterUnits: 'objectBoundingBox',
                    primitiveUnits: 'userSpaceOnUse',
                    'color-interpolation-filters': 'sRGB',
                });
                filter.appendChild(this.svgEl('feGaussianBlur', {
                    in: 'SourceGraphic',
                    stdDeviation: String(spec.blur),
                }));
                defs.appendChild(filter);

                const halo = shape.cloneNode(true);
                halo.classList.remove('checkout-shape', 'checkout-segment');
                halo.classList.add('checkout-halo');
                halo.removeAttribute('id');
                halo.removeAttribute('style');
                halo.setAttribute('data-halo-for', checkout);
                halo.setAttribute('fill', spec.color);
                halo.setAttribute('stroke', 'none');
                halo.setAttribute('opacity', String(spec.opacity));
                halo.setAttribute('filter', `url(#${filterId})`);
                shape.parentNode.insertBefore(halo, shape);
            });
        },
        syncInnerStroke(group, brush) {
            const svg = this.$refs.host.querySelector('svg');
            const defs = svg.querySelector('defs');
            const checkout = group.getAttribute('data-checkout');
            const shape = group.querySelector('.checkout-shape');
            const existing = group.querySelector('.checkout-inner-stroke');

            if (!shape || !brush.innerStroke.enabled || brush.innerStroke.width <= 0) {
                existing?.remove();
                return;
            }

            const clipId = `cw-clip-${checkout}`;
            document.getElementById(clipId)?.remove();
            const clip = this.svgEl('clipPath', { id: clipId, clipPathUnits: 'userSpaceOnUse' });
            const clipShape = shape.cloneNode(true);
            clipShape.removeAttribute('id');
            clipShape.removeAttribute('class');
            clipShape.removeAttribute('style');
            clip.appendChild(clipShape);
            defs.appendChild(clip);

            let strokeEl = existing;
            if (!strokeEl) {
                strokeEl = shape.cloneNode(true);
                strokeEl.classList.remove('checkout-shape', 'checkout-segment');
                strokeEl.classList.add('checkout-inner-stroke');
                strokeEl.removeAttribute('id');
                shape.after(strokeEl);
            }

            strokeEl.setAttribute('fill', 'none');
            strokeEl.setAttribute('stroke', brush.innerStroke.color);
            strokeEl.setAttribute('stroke-width', String(brush.innerStroke.width * 2));
            strokeEl.setAttribute('clip-path', `url(#${clipId})`);
        },
        outerGlowCss(glow) {
            if (!glow.enabled || glow.blur <= 0 || glow.opacity <= 0) {
                return '';
            }
            const color = this.hexA(glow.color, glow.opacity);
            const layers = [
                `drop-shadow(0 0 ${glow.blur}px ${color})`,
                `drop-shadow(0 0 ${Math.round(glow.blur * 1.7)}px ${color})`,
            ];
            if (glow.blur >= 16) {
                layers.push(`drop-shadow(0 0 ${Math.round(glow.blur * 2.8)}px ${this.hexA(glow.color, Math.min(1, glow.opacity * 0.5))})`);
            }
            return layers.join(' ');
        },
        syncInnerGlowFilter(group, glow) {
            const svg = this.$refs.host.querySelector('svg');
            const defs = svg.querySelector('defs');
            const id = `cw-inner-${group.getAttribute('data-checkout')}`;
            document.getElementById(id)?.remove();

            const filter = this.svgEl('filter', {
                id,
                x: '-80%',
                y: '-80%',
                width: '260%',
                height: '260%',
                'color-interpolation-filters': 'sRGB',
            });
            filter.appendChild(this.svgEl('feMorphology', {
                in: 'SourceAlpha',
                operator: 'erode',
                radius: String(glow.erode),
                result: 'eroded',
            }));
            filter.appendChild(this.svgEl('feComposite', {
                in: 'SourceAlpha',
                in2: 'eroded',
                operator: 'out',
                result: 'rim',
            }));
            filter.appendChild(this.svgEl('feGaussianBlur', {
                in: 'rim',
                stdDeviation: String(glow.blur),
                result: 'blur',
            }));
            filter.appendChild(this.svgEl('feFlood', {
                'flood-color': glow.color,
                'flood-opacity': String(glow.opacity),
                result: 'flood',
            }));
            filter.appendChild(this.svgEl('feComposite', {
                in: 'flood',
                in2: 'blur',
                operator: 'in',
                result: 'colored',
            }));
            filter.appendChild(this.svgEl('feComposite', {
                in: 'colored',
                in2: 'SourceAlpha',
                operator: 'in',
                result: 'inner',
            }));
            const merge = this.svgEl('feMerge');
            merge.appendChild(this.svgEl('feMergeNode', { in: 'SourceGraphic' }));
            merge.appendChild(this.svgEl('feMergeNode', { in: 'inner' }));
            filter.appendChild(merge);
            defs.appendChild(filter);

            return id;
        },
    }));
});
</script>
@endsection

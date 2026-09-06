export function registerPlayerCareer(alpine) {
    alpine.data('playerCareerDashboard', (initial) => ({
        window: initial.window || '90d',
        source: initial.source || 'all',
        isSelf: !!initial.isSelf,
        hero: initial.hero || {},
        series: initial.series || { x01_average: [], double_pct: [] },
        loading: false,
        fetchUrl: initial.fetchUrl,

        windows: [
            { key: '30d', label: '1 mies.' },
            { key: '90d', label: '3 mies.' },
            { key: '180d', label: '6 mies.' },
            { key: '365d', label: '12 mies.' },
            { key: 'all', label: 'Całość' },
        ],

        sources() {
            const list = [
                { key: 'all', label: 'Wszystkie źródła' },
                { key: 'tournament', label: 'Turnieje' },
                { key: 'quick', label: 'Szybkie gry' },
            ];
            if (this.isSelf) {
                list.push({ key: 'training', label: 'Treningi' });
            }
            return list;
        },

        async applyFilters() {
            if (!this.fetchUrl) {
                return;
            }
            this.loading = true;
            try {
                const url = new URL(this.fetchUrl, window.location.origin);
                url.searchParams.set('window', this.window);
                url.searchParams.set('source', this.source);
                const res = await fetch(url.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                this.hero = data.hero || {};
                this.series = data.series || { x01_average: [], double_pct: [] };
            } finally {
                this.loading = false;
            }
        },

        setWindow(key) {
            if (this.window === key) {
                return;
            }
            this.window = key;
            this.applyFilters();
        },

        setSource(key) {
            if (this.source === key) {
                return;
            }
            this.source = key;
            this.applyFilters();
        },

        formatDelta(value) {
            if (value == null) {
                return null;
            }
            const n = Number(value);
            if (!Number.isFinite(n) || n === 0) {
                return n === 0 ? '0' : null;
            }
            return (n > 0 ? '+' : '') + (Number.isInteger(n) ? String(n) : n.toFixed(1));
        },

        sparkline(points) {
            const values = (points || []).map((p) => p.value).filter((v) => v != null);
            if (values.length < 2) {
                return null;
            }
            const w = 320;
            const h = 96;
            const min = Math.min(...values);
            const max = Math.max(...values);
            const span = max - min || 1;
            const step = (w - 8) / (values.length - 1);
            const coords = values.map((v, i) => {
                const x = 4 + i * step;
                const y = h - 8 - ((v - min) / span) * (h - 16);
                return `${x.toFixed(1)},${y.toFixed(1)}`;
            });
            return {
                w,
                h,
                d: `M ${coords.join(' L ')}`,
            };
        },
    }));
}

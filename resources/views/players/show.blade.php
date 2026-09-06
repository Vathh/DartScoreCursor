@extends('layouts.app')

@section('title', 'Profil – ' . $player->name)

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('playerProfileData', () => ({
        activeTab: 'overview',
        gameHistory: {
            items: @json($gameHistoryItems),
            hasMore: @json($gameHistoryHasMore),
            page: 1,
            loading: false
        },
        loadMoreGames() {
            if (this.gameHistory.loading || !this.gameHistory.hasMore) return;
            this.gameHistory.loading = true;
            fetch('{{ route('players.games', $player) }}?page=' + (this.gameHistory.page + 1), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                this.gameHistory.items = this.gameHistory.items.concat(data.items);
                this.gameHistory.hasMore = data.has_more;
                this.gameHistory.page++;
            })
            .finally(() => { this.gameHistory.loading = false; });
        },
        typeLabel(type) {
            if (type === 'quick') return 'Szybki mecz';
            if (type === 'group') return 'Grupa';
            if (type === 'playoff') return 'Play-off';
            if (type === 'league') return 'Liga';
            if (type === 'training') return 'Trening';
            return type;
        },
        gameUrl(m) {
            if (m?.type === 'league' && m?.id) {
                return '{{ url('/league-games') }}/' + m.id;
            }
            if (!m?.id || !['quick', 'group', 'playoff'].includes(m.type)) {
                return null;
            }
            return '{{ url('/games') }}/' + m.type + '/' + m.id;
        },
        openGame(m) {
            const url = this.gameUrl(m);
            if (url) {
                window.location.href = url;
            }
        }
    }));
});
</script>
@endsection

@section('content')
    <div class="py-6 sm:py-8" x-data="playerProfileData()">
        {{-- Nagłówek profilu --}}
        <div class="card mb-6 !p-4 sm:!p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-2xl sm:text-3xl font-bold text-text break-words">{{ $player->name }}</h1>
                    @if($player->user_id && $player->user)
                        <p class="text-text-secondary mt-2">Zarejestrowany od {{ $player->user->created_at->format('d.m.Y') }}</p>
                    @else
                        <p class="text-text-muted mt-2">Gracz gość</p>
                    @endif
                </div>
                @auth
                    @if($isOwnProfile)
                        <a href="{{ route('players.edit', $player) }}" class="btn btn-mini">Edytuj profil</a>
                    @elseif($canInviteFriend)
                        <form action="{{ route('players.add-friend', $player) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-mini">Dodaj do znajomych</button>
                        </form>
                    @elseif($isFriend)
                        <span class="text-accent font-semibold">Znajomy</span>
                    @elseif($pendingSentInvitation)
                        <span class="text-accent font-semibold">Zaproszenie wysłane</span>
                    @elseif($pendingReceivedInvitation)
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-accent text-sm">Zaproszenie od tego gracza</span>
                            <form action="{{ route('friends.invitations.accept', $pendingReceivedInvitation->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-mini">Akceptuj</button>
                            </form>
                            <form action="{{ route('friends.invitations.reject', $pendingReceivedInvitation->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-mini border border-accent text-accent bg-transparent hover:bg-accent/10">Odrzuć</button>
                            </form>
                        </div>
                    @endif
                @endauth
            </div>

            @if(filled($player->description))
                <div class="mt-4 pt-4 border-t border-border">
                    <p class="text-sm font-semibold text-accent mb-2">Opis</p>
                    <p class="text-text-secondary whitespace-pre-wrap break-words">{{ $player->description }}</p>
                </div>
            @elseif($isOwnProfile)
                <div class="mt-4 pt-4 border-t border-border">
                    <p class="text-text-muted text-sm">Nie masz jeszcze opisu. <a href="{{ route('players.edit', $player) }}" class="text-accent hover:underline">Dodaj go w edycji profilu</a>.</p>
                </div>
            @endif

            @if(!empty($liveGames))
                <div class="mt-4 pt-4 border-t border-border space-y-2">
                    @foreach($liveGames as $liveGame)
                        <a href="{{ $liveGame['liveUrl'] }}"
                           class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-accent/40 bg-accent/10 px-4 py-3 hover:bg-accent/15 transition no-underline">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-semibold bg-accent/25 text-accent">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-accent animate-pulse" aria-hidden="true"></span>
                                        Na żywo
                                    </span>
                                    <span class="text-xs text-text-muted">{{ $liveGame['stageLabel'] }}</span>
                                </div>
                                <p class="text-text font-semibold break-words">
                                    Gra teraz vs {{ $liveGame['opponentName'] }}
                                    @if($liveGame['tournamentName'])
                                        <span class="text-text-secondary font-normal">· {{ $liveGame['tournamentName'] }}</span>
                                    @endif
                                </p>
                            </div>
                            <span class="text-accent text-sm font-semibold shrink-0">Podgląd live →</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Zakładki --}}
        <div class="flex gap-2 mb-6 border-b border-border pb-2 overflow-x-auto">
            <button type="button"
                    @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'bg-success-muted text-success-bright border-border' : 'border-border text-text-secondary hover:bg-bg-elevated'"
                    class="px-4 py-2 rounded-t border font-medium transition whitespace-nowrap shrink-0">
                Przegląd
            </button>
            <button type="button"
                    @click="activeTab = 'history'"
                    :class="activeTab === 'history' ? 'bg-success-muted text-success-bright border-border' : 'border-border text-text-secondary hover:bg-bg-elevated'"
                    class="px-4 py-2 rounded-t border font-medium transition whitespace-nowrap shrink-0">
                Historia meczów
            </button>
            <button type="button"
                    @click="activeTab = 'stats'"
                    :class="activeTab === 'stats' ? 'bg-success-muted text-success-bright border-border' : 'border-border text-text-secondary hover:bg-bg-elevated'"
                    class="px-4 py-2 rounded-t border font-medium transition whitespace-nowrap shrink-0">
                Statystyki
            </button>
            <button type="button"
                    @click="activeTab = 'badges'"
                    :class="activeTab === 'badges' ? 'bg-success-muted text-success-bright border-border' : 'border-border text-text-secondary hover:bg-bg-elevated'"
                    class="px-4 py-2 rounded-t border font-medium transition whitespace-nowrap shrink-0">
                Odznaczenia
            </button>
        </div>

        {{-- Zakładka: Przegląd --}}
        <div x-show="activeTab === 'overview'" x-cloak class="space-y-8">
            @include('players.partials.stats-tables')
        </div>

        {{-- Zakładka: Statystyki --}}
        <div x-show="activeTab === 'stats'" x-cloak>
            @php
                $career = $career ?? ['window' => '90d', 'source' => 'all', 'isSelf' => $isOwnProfile, 'hero' => [], 'series' => ['x01_average' => [], 'double_pct' => []], 'table' => null];
            @endphp
            <div
                x-data="playerCareerDashboard({
                    window: @js($career['window'] ?? '90d'),
                    source: @js($career['source'] ?? 'all'),
                    isSelf: @js($career['isSelf'] ?? $isOwnProfile),
                    hero: @js($career['hero'] ?? []),
                    series: @js($career['series'] ?? ['x01_average' => [], 'double_pct' => []]),
                    table: @js($career['table'] ?? null),
                    fetchUrl: @js(route('players.career', $player)),
                })"
                class="space-y-8"
            >
                <div>
                    <div class="career-section-head">
                        <h2 class="text-xl font-bold text-accent">Kariera</h2>
                        <p class="text-xs text-text-muted" x-show="loading">Aktualizuję…</p>
                    </div>
                    <div
                        class="career-filter-anchor"
                        x-ref="filterAnchor"
                        :style="filterStuck ? { height: filterBarH + 'px' } : null"
                    >
                        <div
                            class="career-filter-bar"
                            x-ref="filterBar"
                            :class="{ 'is-stuck': filterStuck }"
                        >
                            <div class="career-filter-controls">
                                <div class="career-seg" role="group" aria-label="Źródło">
                                    <template x-for="s in sources()" :key="s.key">
                                        <button type="button"
                                                class="career-seg-btn"
                                                :class="{ 'is-on': source === s.key }"
                                                :aria-pressed="(source === s.key).toString()"
                                                @click="setSource(s.key)">
                                            <span x-text="s.label"></span>
                                        </button>
                                    </template>
                                </div>
                                <div class="career-seg" role="group" aria-label="Okres">
                                    <template x-for="w in windows" :key="w.key">
                                        <button type="button"
                                                class="career-seg-btn"
                                                :class="{ 'is-on': window === w.key }"
                                                :aria-pressed="(window === w.key).toString()"
                                                @click="setWindow(w.key)">
                                            <span x-text="w.label"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-bg-elevated rounded-lg p-4 border border-border">
                            <p class="text-xs uppercase tracking-wide text-text-muted">Mecze</p>
                            <p class="text-2xl font-bold text-text mt-1" x-text="hero.games ?? 0"></p>
                        </div>
                        <div class="bg-bg-elevated rounded-lg p-4 border border-border">
                            <p class="text-xs uppercase tracking-wide text-text-muted">Średnia 3-dartowa (X01)</p>
                            <p class="text-2xl font-bold text-text mt-1" x-text="hero.hasX01 ? hero.x01Average : '–'"></p>
                            <p class="text-xs mt-1" x-show="formatDelta(hero.x01AverageDelta)"
                               :class="hero.x01AverageDelta > 0 ? 'text-success-bright' : 'text-text-muted'"
                               x-text="formatDelta(hero.x01AverageDelta) ? ('vs poprz. okno ' + formatDelta(hero.x01AverageDelta)) : ''"></p>
                        </div>
                        <div class="bg-bg-elevated rounded-lg p-4 border border-border">
                            <p class="text-xs uppercase tracking-wide text-text-muted">Double %</p>
                            <p class="text-2xl font-bold text-text mt-1" x-text="hero.hasDoubles && hero.doublePct != null ? (hero.doublePct + '%') : '–'"></p>
                            <p class="text-xs text-text-muted mt-1" x-text="hero.doubleLabel || ''"></p>
                            <p class="text-xs mt-1" x-show="formatDelta(hero.doublePctDelta)"
                               :class="hero.doublePctDelta > 0 ? 'text-success-bright' : 'text-text-muted'"
                               x-text="formatDelta(hero.doublePctDelta) ? ('vs poprz. okno ' + formatDelta(hero.doublePctDelta)) : ''"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="bg-bg-elevated rounded-lg p-4 border border-border">
                            <h3 class="font-semibold text-text mb-3">Trend średniej X01</h3>
                            @include('players.partials.career-trend-chart', [
                                'chartVar' => 'x01Chart',
                                'htmlVar' => 'x01ChartHtml',
                                'emptyText' => 'Za mało gier X01 w tym oknie, żeby narysować wykres.',
                            ])
                        </div>
                        <div class="bg-bg-elevated rounded-lg p-4 border border-border">
                            <h3 class="font-semibold text-text mb-3">Trend double %</h3>
                            @include('players.partials.career-trend-chart', [
                                'chartVar' => 'doubleChart',
                                'htmlVar' => 'doubleChartHtml',
                                'emptyText' => 'Brak śledzonych dubli w tym oknie (nie pokazujemy 0%).',
                            ])
                        </div>
                    </div>
                </div>

                @include('players.partials.career-filter-tables')
            </div>
        </div>

        {{-- Zakładka: Historia meczów --}}
        <div x-show="activeTab === 'history'" x-cloak>
            <section>
                <h2 class="text-xl font-bold text-accent mb-4">Ostatnie mecze</h2>
                <div class="bg-bg-elevated rounded-lg border border-border overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-text-secondary">
                            <thead>
                                <tr class="border-b border-border bg-bg-deep/50">
                                    <th class="px-3 sm:px-4 py-3 whitespace-nowrap">Data</th>
                                    <th class="px-3 sm:px-4 py-3 whitespace-nowrap">Typ</th>
                                    <th class="px-3 sm:px-4 py-3 min-w-[8rem]">Przeciwnik / przeciwnicy</th>
                                    <th class="px-3 sm:px-4 py-3 whitespace-nowrap">Wynik</th>
                                    <th class="px-3 sm:px-4 py-3 whitespace-nowrap">Score</th>
                                    <th class="px-3 sm:px-4 py-3 whitespace-nowrap">Turniej</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(m, i) in gameHistory.items" :key="i">
                                    <tr
                                        class="border-b border-border/50 transition"
                                        :class="gameUrl(m) ? 'hover:bg-bg-deep/30 cursor-pointer' : ''"
                                        :tabindex="gameUrl(m) ? 0 : null"
                                        :role="gameUrl(m) ? 'link' : null"
                                        :aria-label="gameUrl(m) ? ('Szczegóły meczu: ' + (m.opponents || '')) : null"
                                        @click="openGame(m)"
                                        @keydown.enter.prevent="openGame(m)"
                                        @keydown.space.prevent="openGame(m)"
                                    >
                                        <td class="px-3 sm:px-4 py-3 whitespace-nowrap text-sm" x-text="m.date_formatted"></td>
                                        <td class="px-3 sm:px-4 py-3 whitespace-nowrap text-sm" x-text="typeLabel(m.type)"></td>
                                        <td class="px-3 sm:px-4 py-3 text-sm" x-text="m.opponents"></td>
                                        <td class="px-3 sm:px-4 py-3 whitespace-nowrap text-sm">
                                            <span :class="m.result === 'wygrana' ? 'text-accent font-semibold' : 'text-text-muted'" x-text="m.result"></span>
                                        </td>
                                        <td class="px-3 sm:px-4 py-3 whitespace-nowrap text-sm" x-text="m.score || '–'"></td>
                                        <td class="px-3 sm:px-4 py-3 text-sm" x-text="m.tournament_name || '–'"></td>
                                    </tr>
                                </template>
                                <tr x-show="gameHistory.items.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-text-muted">Brak meczów w historii.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-border flex justify-center">
                        <button type="button"
                                @click="loadMoreGames()"
                                x-show="gameHistory.hasMore"
                                :disabled="gameHistory.loading"
                                class="btn btn-mini disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-text="gameHistory.loading ? 'Ładowanie…' : 'Załaduj więcej'"></span>
                        </button>
                    </div>
                </div>
            </section>
        </div>

        {{-- Zakładka: Odznaczenia --}}
        <div x-show="activeTab === 'badges'" x-cloak></div>
    </div>
@endsection

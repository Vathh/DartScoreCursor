{{-- Przegląd: aktywność i społeczność --}}
@php
    $activity = $overview['activity'] ?? [];
    $social = $overview['social'] ?? [];
    $activityDays = (int) ($activity['days'] ?? 0);
    $longestStreak = (int) ($activity['longestStreak'] ?? 0);
    $gamesPerDay = $activity['gamesPerDay'] ?? null;
    $recentDays = $activity['recentDays'] ?? [];
    $friends = (int) ($social['friends'] ?? 0);
    $uniqueOpponents = (int) ($social['uniqueOpponents'] ?? 0);
    $rivals = $social['rivals'] ?? [];
@endphp
<section>
    <div class="overview-pair overview-pair--pack">
        <div class="overview-panel overview-panel--activity">
            <p class="overview-summary-kicker">Aktywność</p>
            <div class="overview-panel-body">
                <div>
                    <p class="overview-summary-rate">{{ $activityDays }}</p>
                    <p class="overview-summary-rate-label">dni z grą</p>
                </div>
                @if($recentDays !== [])
                    <div class="overview-week-wrap">
                        <p class="overview-week-caption">Ostatnie 7 dni</p>
                        <ol class="overview-week" aria-label="Dni z grą w ostatnich 7 dniach">
                            @foreach($recentDays as $day)
                                <li>
                                    <span class="overview-week-dot {{ $day['played'] ? 'is-on' : '' }}" title="{{ $day['date'] }}"></span>
                                    <span class="overview-week-label">{{ $day['label'] }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </div>
            <dl class="overview-panel-meta">
                <div>
                    <dt>Najdłuższa seria</dt>
                    <dd>{{ $longestStreak }}</dd>
                </div>
                <div>
                    <dt>Mecze / dzień</dt>
                    <dd>{{ $gamesPerDay ?? '–' }}</dd>
                </div>
            </dl>
        </div>

        <div class="overview-panel overview-panel--social">
            <p class="overview-summary-kicker">Społeczność</p>
            <div class="overview-panel-body">
                <div>
                    <p class="overview-summary-rate">{{ $friends }}</p>
                    <p class="overview-summary-rate-label">znajomi</p>
                    <div class="overview-side-stat">
                        <p class="overview-side-stat-value">{{ $uniqueOpponents }}</p>
                        <p class="overview-side-stat-label">unikalni przeciwnicy</p>
                    </div>
                </div>
                @if($rivals !== [])
                    <div class="overview-rivals-wrap">
                        <p class="overview-week-caption">Najczęstsi przeciwnicy</p>
                        <ul class="overview-rivals" aria-label="Najczęstsi przeciwnicy">
                            @foreach($rivals as $rival)
                                <li>
                                    <a href="{{ route('players.show', $rival['id']) }}" class="overview-rival-name">{{ $rival['name'] }}</a>
                                    <span class="overview-rival-games">{{ $rival['games'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Przegląd: aktywność i społeczność --}}
@php
    $activity = $overview['activity'] ?? [];
    $social = $overview['social'] ?? [];
    $activityDays = (int) ($activity['days'] ?? 0);
    $longestStreak = (int) ($activity['longestStreak'] ?? 0);
    $friends = (int) ($social['friends'] ?? 0);
    $uniqueOpponents = (int) ($social['uniqueOpponents'] ?? 0);
@endphp
<section>
    <div class="overview-pair">
        <div class="overview-panel overview-panel--activity">
            <p class="overview-summary-kicker">Aktywność</p>
            <p class="overview-summary-rate">{{ $activityDays }}</p>
            <p class="overview-summary-rate-label">dni z grą</p>
            <dl class="overview-recent-meta">
                <div>
                    <dt>Najdłuższa seria</dt>
                    <dd>{{ $longestStreak }}</dd>
                </div>
            </dl>
        </div>

        <div class="overview-panel overview-panel--social">
            <p class="overview-summary-kicker">Społeczność</p>
            <p class="overview-summary-rate">{{ $friends }}</p>
            <p class="overview-summary-rate-label">znajomi</p>
            <dl class="overview-recent-meta">
                <div>
                    <dt>Unikalni przeciwnicy</dt>
                    <dd>{{ $uniqueOpponents }}</dd>
                </div>
            </dl>
        </div>
    </div>
</section>

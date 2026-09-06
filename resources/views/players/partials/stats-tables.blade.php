{{-- Przegląd: jedna tabela, dwie kolumny źródeł, okno 3 mies. --}}
@php
    $overviewQuick = $overviewSplit['quick'] ?? [];
    $overviewTournament = $overviewSplit['tournament'] ?? [];
    $overviewCell = static function (array $stats, string $key): string {
        if ($key === 'fastest_qf') {
            return $stats['fastest_qf'] !== null ? $stats['fastest_qf'].' lotek' : '–';
        }
        if (in_array($key, ['avg_three_darts', 'highest_hf'], true)) {
            return $stats[$key] !== null ? (string) $stats[$key] : '–';
        }

        return (string) ($stats[$key] ?? 0);
    };
    $overviewRows = [
        ['Rozegrane mecze', 'games'],
        ['Średnia (3 lotki)', 'avg_three_darts'],
        ['Najwyższy finish (HF)', 'highest_hf'],
        ['Najszybsza lotka (QF)', 'fastest_qf'],
        ['Ilość 180 (max)', 'count_max'],
        ['Ilość 170+ (bez 180)', 'count_170_plus'],
        ['Ilość finishów 100+ (HF)', 'count_hf'],
        ['Ilość szybkich lotek (QF)', 'count_qf'],
    ];
@endphp
<section>
    <h2 class="text-xl font-bold text-accent">Forma</h2>
    <p class="text-xs text-text-muted mt-1 mb-4">Przedstawione statystyki pochodzą z ostatnich 3 miesięcy.</p>
    <div class="bg-bg-elevated rounded-lg p-6 border border-border overflow-x-auto">
        <table class="w-full text-left text-text-secondary">
            <thead>
                <tr class="border-b border-border">
                    <th class="pb-2 pr-4">Metryka</th>
                    <th class="pb-2 pr-4 text-right whitespace-nowrap">Turnieje</th>
                    <th class="pb-2 text-right whitespace-nowrap">Mecze szybkie</th>
                </tr>
            </thead>
            <tbody>
                @foreach($overviewRows as [$label, $key])
                    <tr class="border-b border-border/50">
                        <td class="py-2 pr-4">{{ $label }}</td>
                        <td class="py-2 pr-4 text-right tabular-nums">{{ $overviewCell($overviewTournament, $key) }}</td>
                        <td class="py-2 text-right tabular-nums">{{ $overviewCell($overviewQuick, $key) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

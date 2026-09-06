{{-- Jedna tabela w Statystykach: te same filtry źródła i okna co Kariera. --}}
<section>
    <h2 class="text-xl font-bold text-accent mb-4">Podsumowanie</h2>
    <div class="bg-bg-elevated rounded-lg p-6 border border-border overflow-x-auto">
        <table class="w-full text-left text-text-secondary">
            <thead>
                <tr class="border-b border-border">
                    <th class="pb-2 pr-4">Metryka</th>
                    <th class="pb-2">Wartość</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-border/50"><td class="py-2 pr-4">Rozegrane mecze</td><td x-text="table.games ?? 0"></td></tr>
                <tr class="border-b border-border/50"><td class="py-2 pr-4">Średnia (3 lotki)</td><td x-text="dash(table.avg_three_darts)"></td></tr>
                <tr class="border-b border-border/50"><td class="py-2 pr-4">Najwyższy finish (HF)</td><td x-text="dash(table.highest_hf)"></td></tr>
                <tr class="border-b border-border/50"><td class="py-2 pr-4">Najszybsza lotka (QF)</td><td x-text="qf(table.fastest_qf)"></td></tr>
                <tr class="border-b border-border/50"><td class="py-2 pr-4">Ilość 180 (max)</td><td x-text="table.count_max ?? 0"></td></tr>
                <tr class="border-b border-border/50"><td class="py-2 pr-4">Ilość 170+ (bez 180)</td><td x-text="table.count_170_plus ?? 0"></td></tr>
                <tr class="border-b border-border/50"><td class="py-2 pr-4">Ilość finishów 100+ (HF)</td><td x-text="table.count_hf ?? 0"></td></tr>
                <tr class="border-b border-border/50"><td class="py-2 pr-4">Ilość szybkich lotek (QF)</td><td x-text="table.count_qf ?? 0"></td></tr>
            </tbody>
        </table>
    </div>
</section>

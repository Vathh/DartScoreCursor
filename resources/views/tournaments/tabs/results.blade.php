<div class="overflow-x-auto rounded-lg p-4  bg-darker-bg border-border mt-10">
    <p class="text-center mb-3">Wyniki</p>
    <table class="border-collapse text-sm text-text-primary min-w-full">
        <thead>
        <tr class="bg-dark-bg text-text-muted hover:bg-thead-hover transition">
            <th class="px-3 py-2 text-left">Zawodnik</th>
            <th class="px-2 py-2 text-center">Miejsce</th>
            <th class="px-2 py-2 text-center">Punkty</th>
            <th class="px-2 py-2 text-center">Etap</th>
        </tr>
        </thead>

        <tbody class="divide-y divide-border">
        @foreach($results as $result)
            <tr class="hover:bg-row-hover transition">
                <td class="px-3 py-2 font-medium text-text-primary whitespace-nowrap">
                    {{ $result['player']->name }}
                </td>
                <td class="px-2 py-2 text-center">{{ $result['place'] }}</td>
                <td class="px-2 py-2 text-center">{{ $result['points'] }}</td>
                <td class="px-2 py-2 text-center flex-wrap">{{ $result['stage']->label() }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

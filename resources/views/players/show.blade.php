@extends('layouts.app')

@section('title', 'Profil – ' . $player->name)

@section('content')
    <div class="py-8">
        {{-- Przegląd --}}
        <div class="bg-lighter-bg rounded-lg p-6 mb-8 border border-border">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-light-green">{{ $player->name }}</h1>
                    @if($player->user_id && $player->user)
                        <p class="text-light-white mt-2">Zarejestrowany od {{ $player->user->created_at->format('d.m.Y') }}</p>
                    @else
                        <p class="text-light-gray mt-2">Gracz gość</p>
                    @endif
                </div>
                @auth
                    @if($canAddFriend)
                        <form action="{{ route('players.add-friend', $player) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-mini">Dodaj do znajomych</button>
                        </form>
                    @elseif($isFriend)
                        <span class="text-light-green font-semibold">Znajomy</span>
                    @endif
                @endauth
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-900/50 border border-green-600 rounded text-light-green">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-900/50 border border-red-600 rounded text-light-red">{{ session('error') }}</div>
        @endif

        {{-- Statystyki: mecze szybkie --}}
        <section class="mb-8">
            <h2 class="text-xl font-bold text-light-orange mb-4">Statystyki – mecze szybkie</h2>
            <div class="bg-lighter-bg rounded-lg p-6 border border-border overflow-x-auto">
                <table class="w-full text-left text-light-white">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="pb-2 pr-4">Metryka</th>
                            <th class="pb-2">Wartość</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Rozegrane mecze</td><td>{{ $quickStats['matches'] }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Średnia (3 lotki)</td><td>{{ $quickStats['avg_three_darts'] ?? '–' }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Najwyższy finish (HF)</td><td>{{ $quickStats['highest_hf'] ?? '–' }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Najszybsza lotka (QF)</td><td>{{ $quickStats['fastest_qf'] !== null ? $quickStats['fastest_qf'] . ' lotek' : '–' }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Ilość 180 (max)</td><td>{{ $quickStats['count_max'] }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Ilość 170+ (bez 180)</td><td>{{ $quickStats['count_170_plus'] }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Ilość finishów 100+ (HF)</td><td>{{ $quickStats['count_hf'] }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Ilość szybkich lotek (QF)</td><td>{{ $quickStats['count_qf'] }}</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Statystyki: turnieje --}}
        <section>
            <h2 class="text-xl font-bold text-light-orange mb-4">Statystyki – turnieje</h2>
            <div class="bg-lighter-bg rounded-lg p-6 border border-border overflow-x-auto">
                <table class="w-full text-left text-light-white">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="pb-2 pr-4">Metryka</th>
                            <th class="pb-2">Wartość</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Rozegrane mecze</td><td>{{ $tournamentStats['matches'] }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Średnia (3 lotki)</td><td>{{ $tournamentStats['avg_three_darts'] ?? '–' }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Najwyższy finish (HF)</td><td>{{ $tournamentStats['highest_hf'] ?? '–' }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Najszybsza lotka (QF)</td><td>{{ $tournamentStats['fastest_qf'] !== null ? $tournamentStats['fastest_qf'] . ' lotek' : '–' }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Ilość 180 (max)</td><td>{{ $tournamentStats['count_max'] }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Ilość 170+ (bez 180)</td><td>{{ $tournamentStats['count_170_plus'] }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Ilość finishów 100+ (HF)</td><td>{{ $tournamentStats['count_hf'] }}</td></tr>
                        <tr class="border-b border-border/50"><td class="py-2 pr-4">Ilość szybkich lotek (QF)</td><td>{{ $tournamentStats['count_qf'] }}</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

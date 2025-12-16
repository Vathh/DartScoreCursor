@extends('layouts.app')

@section('title', $tournament ? $tournament->name : 'Szczegóły')

@section('content')

    <div class="flex min-h-screen bg-dark-bg text-light-white">

        @seasonAdmin($season)
        <aside class="w-72 backdrop-blur bg-white/5 border-r border-white/10 p-6 flex flex-col">
            <h2 class="text-light-green font-bold text-lg mb-6 tracking-wide">⚙️ Zarządzanie turniejem</h2>

            <nav class="flex flex-col space-y-3">
                <a href="{{ route('tournaments.start', $tournament->id) }}"
                   class="flex items-center gap-3 bg-white/10 hover:bg-white/15 px-4 py-3 rounded-lg transition">
                    ➕ Rozpocznij turniej
                </a>
            </nav>
        </aside>
        @endseasonAdmin

        <div class="flex-1 p-10 flex justify-center">
            <div class="max-w-3xl w-full">

                <h2 class="text-4xl font-bold text-light-green mb-6 tracking-wide hover:text-light-orange transition-all duration-300 hover:cursor-pointer">
                    <a href="{{ route('leagues.show', $season->league->id) }}">{{ $season->league->name }}</a>
                </h2>

                <h1 class="text-3xl font-bold text-light-green mb-6 tracking-wide hover:text-light-orange transition-all duration-300 hover:cursor-pointer">
                    <a href="{{ route('seasons.show', $season->id) }}">{{ $season->name }}</a>
                </h1>

                <h1 class="text-2xl font-bold text-light-orange mb-6 tracking-wide">{{ $tournament->name }}</h1>

                <div class="bg-white/5 border border-white/10 p-6 rounded-xl shadow-lg backdrop-blur">
                    <p class="mb-2"><span
                            class="text-light-green font-semibold">Data rozgrywek:</span> {{ $tournament->getDate() }}
                    </p>
                </div>

                @if($tournament->groupStandings->count() > 0)
                        <div class="overflow-x-auto rounded-lg p-4  bg-darker-bg border-border mt-10">
                            <p class="text-center mb-3">Grupa 1</p>
                            <table class="border-collapse text-sm text-text-primary min-w-full">
                                <thead>
                                <tr class="bg-dark-bg text-text-muted hover:bg-thead-hover transition">
                                    <th class="px-3 py-2 text-left">Zawodnik</th>
{{--                                    @foreach($tournament->groupStandings as $standing)--}}
{{--                                        <th class="px-2 py-2 text-center">{{ $standing-> }}</th>--}}
{{--                                    @endforeach--}}
                                    <th class="px-2 py-2 text-center">P1</th>
                                    <th class="px-2 py-2 text-center">P2</th>
                                    <th class="px-2 py-2 text-center">P3</th>
                                    <th class="px-2 py-2 text-center">P4</th>
                                    <th class="px-2 py-2 text-center">W</th>
                                    <th class="px-2 py-2 text-center">L</th>
                                    <th class="px-2 py-2 text-center">Legi</th>
                                    <th class="px-2 py-2 text-center">Pkt</th>
                                    <th class="px-2 py-2 text-center">Miejsce</th>
                                </tr>
                                </thead>

                                <tbody class="divide-y divide-border">
                                <tr class="hover:bg-row-hover transition">
                                    <td class="px-3 py-2 font-medium text-text-primary whitespace-nowrap">
                                        Player 1
                                    </td>

                                    <td class="px-2 py-2 text-center bg-dark-bg text-text-muted">
                                        X
                                    </td>
                                    <td class="px-2 py-2 text-center">2:1</td>
                                    <td class="px-2 py-2 text-center">1:2</td>
                                    <td class="px-2 py-2 text-center">2:0</td>

                                    <td class="px-2 py-2 text-center">2</td>
                                    <td class="px-2 py-2 text-center">1</td>
                                    <td class="px-2 py-2 text-center">5:3</td>
                                    <td class="px-2 py-2 text-center">4</td>
                                    <td class="px-2 py-2 text-center font-semibold text-light-green">1</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                @endif
            </div>
        </div>

    </div>

@endsection


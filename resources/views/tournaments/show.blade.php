@extends('layouts.app')

@section('title', $tournament ? $tournament->name : 'Szczegóły')

@section('content')

    <div class="flex min-h-screen bg-dark-bg text-light-white">

        @seasonAdmin($season)
        <aside class="w-72 backdrop-blur bg-white/5 border-r border-white/10 p-6 flex flex-col">
            <h2 class="text-light-green font-bold text-lg mb-6 tracking-wide">⚙️ Zarządzanie turniejem</h2>

            <nav class="flex flex-col space-y-3">
{{--                <a href="{{ route('tournaments.create') }}?seasonId={{ $tournament->id }}"--}}
{{--                   class="flex items-center gap-3 bg-white/10 hover:bg-white/15 px-4 py-3 rounded-lg transition">--}}
{{--                    ➕ Rozpocznij turniej--}}
{{--                </a>--}}
{{--                <a href="{{ route('seasons.admins', $tournament->id) }}"--}}
{{--                   class="flex items-center gap-3 bg-white/10 hover:bg-white/15 px-4 py-3 rounded-lg transition">--}}
{{--                    ‍💼 Administratorzy--}}
{{--                </a>--}}
{{--                <a href="{{ route('seasons.edit', ['season' => $tournament->id]) }}"--}}
{{--                   class="flex items-center gap-3 bg-white/10 hover:bg-white/15 px-4 py-3 rounded-lg transition">--}}
{{--                    ✏️ Edytuj sezon--}}
{{--                </a>--}}
{{--                <a href="{{ route('seasons.relatedUsers', $tournament->id) }}"--}}
{{--                   class="flex items-center gap-3 bg-white/10 hover:bg-white/15 px-4 py-3 rounded-lg transition">--}}
{{--                    👨‍👨‍👦 Powiązani użytkownicy--}}
{{--                </a>--}}
{{--                <a href="{{ route('seasons.guests', $tournament->id) }}"--}}
{{--                   class="flex items-center gap-3 bg-white/10 hover:bg-white/15 px-4 py-3 rounded-lg transition">--}}
{{--                    👨‍👨‍👦 Goście--}}
{{--                </a>--}}
                {{--                    <a href="#" class="flex items-center gap-3 bg-light-red/20 hover:bg-light-red/30 px-4 py-3 rounded-lg transition">--}}
                {{--                        🗑️ Usuń ligę--}}
                {{--                    </a>--}}
            </nav>
        </aside>
        @endseasonAdmin

        <div class="flex-1 p-10 flex justify-center">
            <div class="max-w-3xl w-full">

                <h1 class="text-4xl font-bold text-light-green mb-6 tracking-wide hover:text-light-orange transition-all duration-300 hover:cursor-pointer">
                    <a href="{{ route('leagues.show', $tournament->season->id) }}">{{ $tournament->season->name }}</a>
                </h1>

                <h1 class="text-4xl font-bold text-light-orange mb-6 tracking-wide">{{ $tournament->name }}</h1>

                <div class="bg-white/5 border border-white/10 p-6 rounded-xl shadow-lg backdrop-blur">
                    <p class="mb-2"><span
                            class="text-light-green font-semibold">Data rozgrywek:</span> {{ $tournament->getDate() }}
                    </p>
                </div>

            </div>
        </div>

    </div>

@endsection


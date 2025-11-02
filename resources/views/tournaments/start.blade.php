@extends('layouts.app')

@section('title', 'Edycja powiązanych użytkowników')

@section('content')
    <div class="container mx-auto py-8">

        <h1 class="text-2xl font-bold text-light-green mb-6">
            Start turnieju: {{ $tournament->name }}
        </h1>

        <div
            x-data="{ selected: [] }"
            class="mb-8 bg-lighter-bg p-6 rounded-lg shadow"
        >
            <h2 class="text-xl font-semibold text-light-orange mb-4">
                Gracze powiązani
            </h2>

            @if(empty($players))
                <p class="text-light-white">
                    Brak użytkowników powiązanych z tym sezonem.
                </p>
            @else
                <div class="flex flex-wrap gap-3">
                    @foreach($players as $player)
                        <div
                            x-on:click="selected.includes({{ $player->id }})
                        ? selected = selected.filter(id => id !== {{ $player->id }})
                        : selected.push({{ $player->id }})"
                            x-bind:class="selected.includes({{ $player->id }})
                        ? 'bg-light-green text-dark-bg'
                        : 'bg-dark-bg text-light-white'"
                            class="cursor-pointer px-4 py-2 rounded-lg transition duration-200 shadow hover:shadow-lg select-none"
                        >
                            {{ $player->name }}
                        </div>
                    @endforeach
                </div>

                {{-- Formularz do wysłania zaznaczonych --}}
                <form action="{{ route('tournaments.start', $tournament->id) }}" method="POST" class="mt-6">
                    @csrf
                    <input type="hidden" name="selected_players" x-bind:value="JSON.stringify(selected)">
                    <button
                        type="submit"
                        class="btn btn-primary px-6 py-2 mt-4"
                        x-bind:disabled="selected.length === 0"
                    >
                        Start turnieju
                    </button>
                </form>
            @endif
        </div>

        <div>
            <h2 class="text-2xl text-light-green text-center mb-7">Dodawanie użytkowników</h2>
            <div>
                <p><a>Edycja powiązanych użytkowników</a></p>
                <p><a>Edycja graczy niezarejestrowanych</a></p>
            </div>
        </div>

        <div class="flex justify-center mt-5">
            <a href="{{ route('tournaments.show', ['tournament' => $tournament->id]) }}" class="btn btn-primary">
                Powrót
            </a>
        </div>
    </div>
@endsection

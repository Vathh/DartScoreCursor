@extends('layouts.app')

@section('title', 'Tworzenie nowego turnieju')

@section('content')

    <div class="flex justify-center items-center min-h-[70vh]">
        <form class="bg-lighter-bg rounded-2xl p-20 " action="{{ route('tournaments.store') }}?seasonId={{ $seasonId }}" method="POST">
            @csrf
            <div class="flex flex-col items-center">
                <h1 class="text-center text-light-green mb-10 text-2xl">Tworzenie nowego turnieju</h1>

                <label class="mb-3 text-xl text-light-orange" for="login"><b>Nazwa turnieju</b></label>
                <input class="mb-5 input-orange"
                       type="text"
                       placeholder="Wprowadź nazwę turnieju"
                       name="tournamentName"
                       value="{{ old('tournamentName') }}"
                       required>

                <label class="mb-3 text-xl text-light-orange" for="login"><b>Data wydarzenia</b></label>
                <input class="mb-5 input-orange"
                       type="date"
                       name="date"
                       value="{{ old('date') }}"
                       required>

                <button class="btn btn-primary mt-8" type="submit" name="loginBtn">Stwórz turniej</button>

                <a href="{{ route('seasons.show', ['season' => $seasonId]) }}" class="btn btn-primary mt-5" type="submit">Powrót</a>

                <x-errors/>
            </div>
        </form>
    </div>

@endsection


@extends('layouts.app')

@section('title', 'Ligi')

@section('content')
    @php
        $toasts = [];
        if(session('success')) {
            $toasts[] = ['type' => 'success', 'text' => session('success')];
        }
        if(session('error')) {
            $toasts[] = ['type' => 'error', 'text' => session('error')];
        }
    @endphp

    <x-alert :messages="$toasts" duration="4000" />

    <div class="flex">
        @if($leagues->isEmpty())
            <p>Brak.</p>
        @else
            @foreach($leagues as $league)
                <div class="bg-lighter-bg shadow rounded-lg p-6 hover:shadow-xl hover:cursor-pointer transition">
                    <h3 class="text-xl font-semibold mb-2 text-light-orange">{{ $league->name }}</h3>
                    <p class="mb-2 text-light-orange">Ostatnia aktywność : 05-10-2025</p>
                    <a href="#" class="text-light-green hover:underline font-semibold transition">Szczegóły</a>
                </div>
            @endforeach
        @endif
    </div>

    @auth
        <a href="{{ route('league.leagueCreator') }}"
           class="fixed bottom-30 right-20 btn-primary py-5 px-8 rounded-xl font-bold">
            Stwórz nową ligę
        </a>
    @endauth

@endsection


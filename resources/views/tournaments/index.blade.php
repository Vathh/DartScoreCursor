@extends('layouts.app')

@section('title', 'Ligi')

@section('content')

    <div class="flex flex-wrap gap-1 items-center justify-center pt-10">
        @if($tournaments->isEmpty())
            <p>Brak.</p>
        @else
            @foreach($tournaments as $tournament)
                <a href="{{ route('seasons.show', ['season' => $tournament->id]) }}">
                    <div class="bg-lighter-bg shadow rounded-lg p-6 hover:shadow-xl hover:cursor-pointer hover:bg-[#333333] transition">
                        <h3 class="btn__title">{{ $tournament->getDate() }}</h3>
                        <p class="btn__description">Ostatnia aktywność : {{ $tournament->getUpdatedAtDate() }}</p>
                    </div>
                </a>
            @endforeach
        @endif
    </div>

    @canCreateLeagues
    <a href="{{ route('seasons.create') }}"
       class="fixed bottom-30 right-20 btn-primary py-5 px-8 rounded-xl font-bold">
        Stwórz nowy sezon
    </a>
    @endcanCreateLeagues

@endsection


@extends('layouts.app')

@section('title', 'Sezony')

@section('content')

    <div class="max-w-6xl mx-auto px-4 pt-10 pb-28">
        @if($seasons->isEmpty())
            <p class="text-center text-[#c5c5c5]">Brak sezonów.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($seasons as $season)
                    <a href="{{ route('seasons.show', ['season' => $season->id]) }}" class="block group">
                        <div
                            class="bg-lighter-bg shadow rounded-lg p-6 h-full min-h-[120px] flex flex-col justify-center hover:shadow-xl hover:cursor-pointer hover:bg-[#333333] transition border border-transparent group-hover:border-white/10">
                            <h3 class="text-lg font-semibold text-white leading-snug mb-3">
                                {{ $season->displayTitle() }}
                            </h3>
                            <p class="text-sm text-[#a8a8a8]">
                                @if($season->getPlayDatesFormatted())
                                    Data rozgrywek: {{ $season->getPlayDatesFormatted() }}
                                @else
                                    <span class="italic">Data rozgrywek: nie ustawiono</span>
                                @endif
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @canCreateLeagues
    <a href="{{ route('seasons.create') }}"
       class="fixed bottom-30 right-20 btn-primary py-5 px-8 rounded-xl font-bold">
        Stwórz nowy sezon
    </a>
    @endcanCreateLeagues

@endsection

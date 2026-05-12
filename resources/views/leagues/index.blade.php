@extends('layouts.app')

@section('title', 'Ligi')

@section('content')

    <div class="max-w-6xl mx-auto px-4 pt-10 pb-28">
        @if($leagues->isEmpty())
            <p class="text-center text-[#c5c5c5]">Brak lig.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($leagues as $league)
                    <a href="{{ route('leagues.show', ['league' => $league->id]) }}" class="block group">
                        <div
                            class="bg-lighter-bg shadow rounded-lg p-6 h-full min-h-[120px] flex flex-col justify-center hover:shadow-xl hover:cursor-pointer hover:bg-[#333333] transition border border-transparent group-hover:border-white/10">
                            <h3 class="text-lg font-semibold text-white leading-snug mb-3">
                                {{ $league->displayTitle() }}
                            </h3>
                            <p class="text-sm text-[#a8a8a8] leading-relaxed">
                                {{ $league->getCardSubtitle() }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @canCreateLeagues
    <a href="{{ route('leagues.create') }}"
       class="fixed bottom-30 right-20 btn-primary py-5 px-8 rounded-xl font-bold">
        Stwórz nową ligę
    </a>
    @endcanCreateLeagues

@endsection

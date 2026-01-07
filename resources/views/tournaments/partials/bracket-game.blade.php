<div class="bg-white/5 border border-white/10 rounded-xl p-3 backdrop-blur shadow-sm">

    {{-- PLAYER 1 --}}
    <div class="flex justify-between items-center mb-1
        {{ $game->winnerId === $game->player1Id ? 'text-light-green font-semibold' : '' }}">
        <span class="truncate">
            {{ $game->player1?->name ?? '—' }}
        </span>
        <span class="ml-2">
            {{ $game->player1Score ?? '' }}
        </span>
    </div>

    {{-- PLAYER 2 --}}
    <div class="flex justify-between items-center
        {{ $game->winnerId === $game->player2Id ? 'text-light-green font-semibold' : '' }}">
        <span class="truncate">
            {{ $game->player2?->name ?? '—' }}
        </span>
        <span class="ml-2">
            {{ $game->player2Score ?? '' }}
        </span>
    </div>

{{--    --}}{{-- STATUS --}}
{{--    <div class="mt-2 text-center text-xs">--}}
{{--        @if($game->status === GameStatus::SCHEDULED)--}}
{{--            <span class="text-text-muted">Zaplanowany</span>--}}
{{--        @elseif($game->status === GameStatus::IN_PROGRESS)--}}
{{--            <span class="text-light-orange">W trakcie</span>--}}
{{--        @elseif($game->status === GameStatus::FINISHED)--}}
{{--            <span class="text-text-muted">Zakończony</span>--}}
{{--        @endif--}}
{{--    </div>--}}
</div>

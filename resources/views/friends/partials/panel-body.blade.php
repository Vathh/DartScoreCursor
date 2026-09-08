@if($receivedFriendInvitations->isNotEmpty())
    <section>
        <h3 class="text-sm font-semibold text-accent mb-2">Zaproszenia</h3>
        <ul class="space-y-2">
            @foreach($receivedFriendInvitations as $invitation)
                <li class="p-3 rounded-lg border border-border bg-bg-elevated/50">
                    <p class="text-text-secondary text-sm mb-2">
                        {{ $invitation->senderPlayer?->name ?? 'Gracz' }}
                    </p>
                    <div class="flex gap-2">
                        <form action="{{ route('friends.invitations.accept', $invitation->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-mini text-xs py-1 px-2">Akceptuj</button>
                        </form>
                        <form action="{{ route('friends.invitations.reject', $invitation->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-xs py-1 px-2 rounded-md border border-accent text-accent hover:bg-accent/10 transition">Odrzuć</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif

<section>
    <h3 class="text-sm font-semibold text-accent mb-2">Twoi znajomi</h3>
@if($friends->isEmpty())
    <x-empty-state
        class="!py-8"
        title="Brak znajomych"
        description="Dodaj graczy z profilu lub wyszukiwarki."
    />
@else
    <ul class="space-y-2">
        @foreach($friends as $friend)
            <li>
                <a href="{{ route('players.show', $friend->friendPlayer->id) }}" class="friends-link">
                    {{ $friend->friendPlayer->name }}
                </a>
            </li>
        @endforeach
    </ul>
@endif
</section>

@if($sentFriendInvitations->isNotEmpty())
    <section>
        <h3 class="text-sm font-semibold text-accent mb-2">Oczekujący</h3>
        <ul class="space-y-2">
            @foreach($sentFriendInvitations as $invitation)
                <li>
                    @if($invitation->receiverPlayer)
                        <a href="{{ route('players.show', $invitation->receiverPlayer->id) }}" class="friends-link">
                            {{ $invitation->receiverPlayer->name }}
                        </a>
                    @else
                        <span class="friends-link text-text-muted pointer-events-none">Gracz</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif

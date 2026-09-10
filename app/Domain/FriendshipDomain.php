<?php

namespace App\Domain;

class FriendshipDomain
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $friendId,
        public readonly PlayerDomain $friendPlayer
    ) {}

    public static function fromData(int $friendshipId, int $userId, int $friendId, PlayerDomain $friendPlayer): self
    {
        return new self(
            id: $friendshipId,
            userId: $userId,
            friendId: $friendId,
            friendPlayer: $friendPlayer
        );
    }
}

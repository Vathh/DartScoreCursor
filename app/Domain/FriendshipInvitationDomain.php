<?php

namespace App\Domain;

class FriendshipInvitationDomain
{
    public function __construct(
        public readonly int $id,
        public readonly int $senderId,
        public readonly int $receiverId,
        public readonly string $status, // 'pending', 'accepted', 'rejected'
        public readonly ?\DateTime $respondedAt,
        public readonly \DateTime $createdAt,
        public readonly ?PlayerDomain $senderPlayer, // Player użytkownika sender
        public readonly ?PlayerDomain $receiverPlayer, // Player użytkownika receiver
    ) {
    }

    public static function fromEloquent(\App\Models\FriendshipInvitation $invitation): self
    {
        $invitation->load(['sender.player', 'receiver.player']);

        return new self(
            id: $invitation->id,
            senderId: $invitation->sender_id,
            receiverId: $invitation->receiver_id,
            status: $invitation->status,
            respondedAt: $invitation->responded_at,
            createdAt: $invitation->created_at,
            senderPlayer: $invitation->sender->player 
                ? PlayerDomain::fromEloquent($invitation->sender->player) 
                : null,
            receiverPlayer: $invitation->receiver->player 
                ? PlayerDomain::fromEloquent($invitation->receiver->player) 
                : null,
        );
    }
}

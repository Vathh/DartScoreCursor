<?php

namespace App\Services\Friends;

use App\Domain\FriendshipDomain;
use App\Domain\FriendshipInvitationDomain;
use App\Repositories\Friends\FriendshipInvitationRepository;
use App\Repositories\Friends\FriendshipRepository;
use App\Services\Push\InvitationPushService;
use Illuminate\Support\Collection;

class FriendshipService
{
    public function __construct(
        private FriendshipRepository $friendshipRepository,
        private FriendshipInvitationRepository $invitationRepository,
        private InvitationPushService $invitationPushService,
    ) {}

    /**
     * Dodaje znajomość między dwoma użytkownikami
     */
    public function addFriend(int $userId, int $friendId): void
    {
        $this->friendshipRepository->addFriendship($userId, $friendId);
    }

    /**
     * Usuwa znajomość między dwoma użytkownikami
     */
    public function removeFriend(int $userId, int $friendId): void
    {
        $this->friendshipRepository->removeFriendship($userId, $friendId);
    }

    /**
     * Pobiera listę znajomych użytkownika
     *
     * @return Collection<int, FriendshipDomain>
     */
    public function getFriends(int $userId): Collection
    {
        return $this->friendshipRepository->getFriends($userId);
    }

    public function countFriends(int $userId): int
    {
        return $this->friendshipRepository->countForUser($userId);
    }

    /**
     * Sprawdza czy użytkownicy są znajomymi
     */
    public function areFriends(int $userId, int $friendId): bool
    {
        return $this->friendshipRepository->areFriends($userId, $friendId);
    }

    /**
     * Wysyła zaproszenie do znajomych
     */
    public function sendInvitation(int $senderId, int $receiverId): FriendshipInvitationDomain
    {
        FriendshipInvitationDomain::assertCanSend(
            senderId: $senderId,
            receiverId: $receiverId,
            areFriends: $this->friendshipRepository->areFriends($senderId, $receiverId),
            hasPendingInvitation: $this->invitationRepository->hasPendingInvitation($senderId, $receiverId),
        );

        $invitation = $this->invitationRepository->create($senderId, $receiverId);

        $this->invitationPushService->notifyFriendInvitation(
            recipientUserId: $invitation->receiverId,
            invitationId: $invitation->id,
            senderName: $invitation->senderPlayer?->name ?? 'Gracz',
        );

        return $invitation;
    }

    /**
     * Akceptuje zaproszenie do znajomych
     *
     * @param  int  $userId  ID użytkownika który akceptuje
     */
    public function acceptInvitation(int $invitationId, int $userId): void
    {
        $this->invitationRepository->accept($invitationId, $userId);
    }

    /**
     * Odrzuca zaproszenie do znajomych
     *
     * @param  int  $userId  ID użytkownika który odrzuca
     */
    public function rejectInvitation(int $invitationId, int $userId): void
    {
        $this->invitationRepository->reject($invitationId, $userId);
    }

    /**
     * Pobiera zaproszenia otrzymane przez użytkownika
     *
     * @return Collection<int, FriendshipInvitationDomain>
     */
    public function getReceivedInvitations(int $userId): Collection
    {
        return $this->invitationRepository->getReceivedInvitations($userId);
    }

    /**
     * Pobiera zaproszenia wysłane przez użytkownika
     *
     * @return Collection<int, FriendshipInvitationDomain>
     */
    public function getSentInvitations(int $userId): Collection
    {
        return $this->invitationRepository->getSentInvitations($userId);
    }

    /**
     * @return array{
     *     friends: Collection<int, FriendshipDomain>,
     *     receivedFriendInvitations: Collection<int, FriendshipInvitationDomain>,
     *     sentFriendInvitations: Collection<int, FriendshipInvitationDomain>
     * }
     */
    public function webPanelData(int $userId): array
    {
        return [
            'friends' => $this->getFriends($userId),
            'receivedFriendInvitations' => $this->getReceivedInvitations($userId),
            'sentFriendInvitations' => $this->getSentInvitations($userId),
        ];
    }

    public function findPendingInvitation(int $senderId, int $receiverId): ?FriendshipInvitationDomain
    {
        return $this->invitationRepository->findPending($senderId, $receiverId);
    }
}

<?php
namespace App\Services;

use App\Domain\LeagueDomain;
use App\Domain\SeasonDomain;
use App\Models\League;
use App\Repositories\SeasonRepository;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

class SeasonService
{

    public function __construct(private SeasonRepository $seasonRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->seasonRepository->getAll()
                                        ->sortByDesc(fn(SeasonDomain $season) => $season->updatedAt)
                                        ->values();
    }

    public function create(
        ?int     $leagueId,
        string  $name,
        array   $adminsIds = [],
        ?string $startDate = null,
        ?string $endDate = null
    ): void
    {
        $league = LeagueDomain::fromEloquent(League::findOrFail($leagueId), ['admins']);
        $leagueAdminsIds = $league->getAdminsIds();
        $allAdminsIds = array_unique(array_merge($leagueAdminsIds, $adminsIds));
        try {
            $this->seasonRepository->create($leagueId, $name, $allAdminsIds, $startDate, $endDate);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'general' => 'Nie udało się dodać sezonu. Spróbuj ponownie.'
            ]);
        }
    }
    public function getRelatedUsers(int $seasonId): Collection
    {
        return $this->seasonRepository->getRelatedUsers($seasonId);
    }

    public function addRelatedUser(int $seasonId, int $userId): void
    {
        $this->seasonRepository->addRelatedUser($seasonId, $userId);
    }

    public function removeRelatedUser(int $seasonId, int $userId): void
    {
        $this->seasonRepository->removeRelatedUser($seasonId, $userId);
    }

    public function addAdmin(int $seasonId, int $userId): void
    {
        $this->seasonRepository->addAdmin($seasonId, $userId);
    }

    public function removeAdmin(int $seasonId, int $userId): void
    {
        $this->seasonRepository->removeAdmin($seasonId, $userId);
    }

}

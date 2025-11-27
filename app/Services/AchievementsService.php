<?php

namespace App\Services;

use App\Repositories\AchievementsRepository;

class AchievementsService
{

    public function __construct(
        private AchievementsRepository $achievementsRepository
    )
    {
    }

    public function createMany(array $achievements): void
    {
        $this->achievementsRepository->createMany($achievements);
    }
}

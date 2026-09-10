<?php

namespace App\Services\Career;

use App\Domain\Career\CareerCollectorRegistry;
use App\Domain\GameScoring\MatchFormat;
use App\Models\Career\TrainingGame;
use App\Models\Users\User;
use App\Repositories\Career\TrainingGameRepository;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class TrainingGameService
{
    private const GAME_TYPES = [
        MatchFormat::DEFAULT_GAME_TYPE,
        MatchFormat::GAME_TYPE_CRICKET,
        MatchFormat::GAME_TYPE_BOB27,
        MatchFormat::GAME_TYPE_ATC,
        MatchFormat::GAME_TYPE_CATCH40,
        MatchFormat::GAME_TYPE_CRICKET56,
    ];

    public function __construct(
        private TrainingGameRepository $trainingGameRepository,
        private PlayerCareerSnapshotService $snapshotService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingest(User $user, array $payload): TrainingGame
    {
        $player = $user->player;
        if ($player === null || ! $player->user_id) {
            abort(403, 'Trening zapisuje się tylko na konto zarejestrowanego gracza.');
        }

        $clientUuid = (string) ($payload['clientUuid'] ?? '');
        if ($clientUuid === '' || ! preg_match('/^[0-9a-fA-F-]{36}$/', $clientUuid)) {
            throw ValidationException::withMessages(['clientUuid' => 'Wymagany UUID klienta.']);
        }

        $gameType = (string) ($payload['gameType'] ?? MatchFormat::DEFAULT_GAME_TYPE);
        if (! in_array($gameType, self::GAME_TYPES, true)) {
            throw ValidationException::withMessages(['gameType' => 'Nieznany typ gry.']);
        }

        $completedAt = CarbonImmutable::parse((string) ($payload['completedAt'] ?? now()->toIso8601String()));
        $rawMetrics = is_array($payload['metrics'] ?? null) ? $payload['metrics'] : [];
        $metrics = $this->normalizeMetrics($gameType, $rawMetrics);
        if ($metrics === null) {
            throw ValidationException::withMessages(['metrics' => 'Brak lotek — snapshot nie zostanie zapisany.']);
        }

        $format = is_array($payload['format'] ?? null) ? $payload['format'] : null;

        $trainingGame = $this->trainingGameRepository->upsertByClientUuid(
            (int) $player->id,
            $clientUuid,
            $gameType,
            $completedAt,
            $format,
            $metrics,
        );

        $this->snapshotService->recordTrainingGame($trainingGame, $metrics);

        return $trainingGame;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>|null
     */
    private function normalizeMetrics(string $gameType, array $raw): ?array
    {
        return CareerCollectorRegistry::normalizeClient($gameType, $raw);
    }
}

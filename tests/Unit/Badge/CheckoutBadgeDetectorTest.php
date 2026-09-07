<?php

namespace Tests\Unit\Badge;

use App\Domain\Badge\BadgeCategory;
use App\Domain\Badge\Checkout\CheckoutBadgeDetector;
use App\Domain\Badge\FinishedGame;
use App\Enums\GameKind;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckoutBadgeDetectorTest extends TestCase
{
    #[Test]
    public function counts_legal_100_plus_checkouts_for_registered_players(): void
    {
        $game = $this->game(visits: [
            $this->visit(1, 121, closed: true),
            $this->visit(1, 121, closed: true),
            $this->visit(1, 40, closed: true),
            $this->visit(1, 180, closed: false),
            $this->visit(2, 170, closed: true),
        ]);

        $events = (new CheckoutBadgeDetector)->detect($game);

        $this->assertCount(2, $events);
        $byKey = [];
        foreach ($events as $event) {
            $byKey[$event['playerId'].':'.$event['badgeKey']] = $event;
        }
        $this->assertSame(2, $byKey['1:121']['amount']);
        $this->assertSame(BadgeCategory::Checkout->value, $byKey['1:121']['category']);
        $this->assertSame(1, $byKey['2:170']['amount']);
    }

    #[Test]
    public function ignores_voided_bust_guest_and_ineligible_format(): void
    {
        $detector = new CheckoutBadgeDetector;

        $voided = $this->game(visits: [
            $this->visit(1, 121, closed: true, voided: true),
            $this->visit(1, 121, closed: true, bust: true),
        ]);
        $this->assertSame([], $detector->detect($voided));

        $guest = $this->game(registered: [1], visits: [
            $this->visit(2, 121, closed: true),
        ]);
        $this->assertSame([], $detector->detect($guest));

        $threeOhOne = $this->game(startingScore: 301, visits: [
            $this->visit(1, 121, closed: true),
        ]);
        $this->assertSame([], $detector->detect($threeOhOne));

        $quick = $this->game(kind: GameKind::QUICK, visits: [
            $this->visit(1, 121, closed: true),
        ]);
        $this->assertSame([], $detector->detect($quick));
    }

    /**
     * @param  list<array{playerId: int, score: int, closedLeg: bool, bust: bool, isVoided: bool}>  $visits
     * @param  list<int>  $registered
     */
    private function game(
        array $visits,
        GameKind $kind = GameKind::GROUP,
        int $startingScore = 501,
        array $registered = [1, 2],
    ): FinishedGame {
        return new FinishedGame(
            kind: $kind,
            sourceId: 9,
            startingScore: $startingScore,
            gameType: 'x01',
            playerIds: [1, 2],
            registeredPlayerIds: $registered,
            visits: $visits,
        );
    }

    /**
     * @return array{playerId: int, score: int, closedLeg: bool, bust: bool, isVoided: bool}
     */
    private function visit(int $playerId, int $score, bool $closed, bool $bust = false, bool $voided = false): array
    {
        return [
            'playerId' => $playerId,
            'score' => $score,
            'closedLeg' => $closed,
            'bust' => $bust,
            'isVoided' => $voided,
        ];
    }
}

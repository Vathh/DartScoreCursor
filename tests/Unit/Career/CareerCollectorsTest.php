<?php

namespace Tests\Unit\Career;

use App\Domain\Career\AtcCareerCollector;
use App\Domain\Career\Bob27CareerCollector;
use App\Domain\Career\Catch40CareerCollector;
use App\Domain\Career\Cricket56CareerCollector;
use App\Domain\Career\CricketCareerCollector;
use App\Domain\Career\X01CareerCollector;
use App\Domain\GameScoring\VisitDartPayload;
use PHPUnit\Framework\TestCase;

class CareerCollectorsTest extends TestCase
{
    public function test_x01_buckets_checkouts_and_sectors(): void
    {
        $visits = [
            [
                'score' => 180,
                'darts_in_visit' => 3,
                'bust' => false,
                'closed_leg' => false,
                'leg_number' => 1,
                'darts' => [
                    ['label' => 'T20', 'points' => 60, 'remainingBefore' => 501],
                    ['label' => 'T20', 'points' => 60, 'remainingBefore' => 441],
                    ['label' => 'T20', 'points' => 60, 'remainingBefore' => 381],
                ],
            ],
            [
                'score' => 40,
                'darts_in_visit' => 1,
                'bust' => false,
                'closed_leg' => true,
                'leg_number' => 1,
                'darts' => [
                    ['label' => 'D20', 'points' => 40, 'remainingBefore' => 40],
                ],
            ],
        ];

        $metrics = X01CareerCollector::fromVisits($visits);

        $this->assertSame(4, $metrics['darts_thrown']);
        $this->assertSame(1, $metrics['visit_scores']['180']);
        $this->assertSame(0, $metrics['visit_scores']['170']);
        $this->assertSame([['score' => 40, 'darts' => 1]], $metrics['checkouts']);
        $this->assertSame(4, $metrics['best_leg_darts']);
        $this->assertTrue($metrics['double_tracked']);
        $this->assertSame(1, $metrics['double_attempts']);
        $this->assertSame(1, $metrics['double_successes']);
        $this->assertSame(4, $metrics['sectors']['20']);
        $this->assertNull(X01CareerCollector::fromVisits([
            ['score' => 60, 'darts_in_visit' => 3, 'bust' => false],
        ])['sectors']);
    }

    public function test_visit_score_170_bucket_excludes_180(): void
    {
        $this->assertSame('180', X01CareerCollector::visitScoreBucket(180));
        $this->assertSame('170', X01CareerCollector::visitScoreBucket(171));
        $this->assertSame('140', X01CareerCollector::visitScoreBucket(140));
    }

    public function test_cricket_marks_hits_points_and_win_darts(): void
    {
        $log = [
            ['playerId' => 1, 'kind' => 'hit', 'segment' => '20', 'multiplier' => 3, 'pointsScored' => 0],
            ['playerId' => 1, 'kind' => 'miss'],
            ['playerId' => 1, 'kind' => 'hit', 'segment' => '20', 'multiplier' => 1, 'pointsScored' => 20],
            ['playerId' => 2, 'kind' => 'hit', 'segment' => '19', 'multiplier' => 3, 'pointsScored' => 0],
        ];

        $winner = CricketCareerCollector::fromDartLog($log, 1, true);
        $loser = CricketCareerCollector::fromDartLog($log, 2, false);

        $this->assertSame(3, $winner['darts_thrown']);
        $this->assertSame(4, $winner['marks']['20']);
        $this->assertSame(2, $winner['hits']['20']);
        $this->assertSame(20, $winner['points']['20']);
        $this->assertSame(3, $winner['win_darts']);
        $this->assertNull($loser['win_darts']);
        $this->assertSame(3, $loser['marks']['19']);
    }

    public function test_bob27_records_mode_score_and_fail_stage(): void
    {
        $log = [
            ['playerId' => 5, 'kind' => 'visit', 'hits' => 0, 'currentTargetIndex' => 0],
            ['playerId' => 5, 'kind' => 'visit', 'hits' => 0, 'currentTargetIndex' => 1],
        ];
        $metrics = Bob27CareerCollector::fromDartLog(
            $log,
            5,
            ['score' => 15, 'eliminated' => true],
            'hard',
            true,
        );

        $this->assertSame('hard', $metrics['bob27_mode']);
        $this->assertSame('with', $metrics['bob27_bull']);
        $this->assertSame(15, $metrics['score']);
        $this->assertFalse($metrics['finished']);
        $this->assertSame('D2', $metrics['ended_at_target']);
        $this->assertSame(6, $metrics['double_attempts']);
    }

    public function test_atc_successes_and_attempts_per_sector(): void
    {
        $log = [
            [
                'playerId' => 3,
                'kind' => 'visit',
                'hits' => 2,
                'finished' => false,
                'boardsSnapshot' => ['3' => ['targetIndex' => 0]],
            ],
            [
                'playerId' => 3,
                'kind' => 'visit',
                'hits' => 1,
                'finished' => true,
                'boardsSnapshot' => ['3' => ['targetIndex' => 20]],
            ],
        ];
        $metrics = AtcCareerCollector::fromDartLog($log, 3);

        $this->assertSame(4, $metrics['darts_thrown']);
        $this->assertSame(['successes' => 1, 'attempts' => 1], $metrics['sectors']['1']);
        $this->assertSame(['successes' => 1, 'attempts' => 1], $metrics['sectors']['2']);
        $this->assertSame(['successes' => 0, 'attempts' => 1], $metrics['sectors']['3']);
        $this->assertSame(['successes' => 1, 'attempts' => 1], $metrics['sectors']['bull']);
    }

    public function test_catch40_outs_and_failed_out(): void
    {
        $log = [
            [
                'playerId' => 8,
                'kind' => 'visit',
                'score' => 61,
                'remainingAfter' => 0,
                'dartsInVisit' => 2,
                'bust' => false,
                'checkout' => true,
                'darts' => [
                    ['label' => 'T15', 'points' => 45, 'remainingBefore' => 61],
                    ['label' => 'D8', 'points' => 16, 'remainingBefore' => 16],
                ],
            ],
            [
                'playerId' => 8,
                'kind' => 'visit',
                'score' => 20,
                'remainingAfter' => 42,
                'dartsInVisit' => 3,
                'bust' => false,
                'checkout' => false,
            ],
            [
                'playerId' => 8,
                'kind' => 'visit',
                'score' => 10,
                'remainingAfter' => 32,
                'dartsInVisit' => 3,
                'bust' => false,
                'checkout' => false,
            ],
        ];
        $metrics = Catch40CareerCollector::fromDartLog($log, 8, ['catch40Score' => 3]);

        $this->assertSame(8, $metrics['darts_thrown']);
        $this->assertSame(3, $metrics['score']);
        $this->assertSame(['out' => 61, 'darts' => 2], $metrics['outs'][0]);
        $this->assertSame(['out' => 62, 'darts' => null], $metrics['outs'][1]);
        $this->assertTrue($metrics['double_tracked']);
        $this->assertSame(1, $metrics['double_attempts']);
        $this->assertSame(1, $metrics['double_successes']);
    }

    public function test_cricket56_marks_per_sector(): void
    {
        $log = [
            [
                'playerId' => 4,
                'kind' => 'visit',
                'currentRoundIndex' => 0,
                'marks' => [3, 1, 0],
            ],
            [
                'playerId' => 4,
                'kind' => 'visit',
                'currentRoundIndex' => 6,
                'marks' => [2, 1, 0],
            ],
        ];
        $metrics = Cricket56CareerCollector::fromDartLog($log, 4, ['score' => 7]);

        $this->assertSame(7, $metrics['score']);
        $this->assertArrayNotHasKey('darts_thrown', $metrics);
        $this->assertSame(['S' => 1, 'D' => 0, 'T' => 1], $metrics['sectors']['15']);
        $this->assertSame(['S' => 1, 'D' => 1, 'T' => 0], $metrics['sectors']['bull']);
    }

    public function test_sector_from_label(): void
    {
        $this->assertSame(20, VisitDartPayload::sectorFromLabel('T20'));
        $this->assertSame(20, VisitDartPayload::sectorFromLabel('D20'));
        $this->assertSame(25, VisitDartPayload::sectorFromLabel('Bull'));
        $this->assertSame(0, VisitDartPayload::sectorFromLabel('miss'));
    }
}

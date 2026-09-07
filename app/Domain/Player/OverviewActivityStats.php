<?php

namespace App\Domain\Player;

use Carbon\CarbonImmutable;

/**
 * Serie aktywności z unikalnych dni kalendarzowych (Europe/Warsaw, Y-m-d).
 */
final class OverviewActivityStats
{
    private const WEEKDAY_LABELS = ['nd', 'pn', 'wt', 'śr', 'cz', 'pt', 'so'];
    /**
     * @param  list<string>  $isoDates
     * @return array{
     *     activity_days: int,
     *     current_streak: int,
     *     longest_streak: int,
     *     last_activity_on: string|null
     * }
     */
    public static function fromDates(array $isoDates, string $todayIso): array
    {
        $dates = array_values(array_unique(array_filter($isoDates, static fn ($value) => is_string($value) && $value !== '')));
        sort($dates);

        $last = $dates === [] ? null : $dates[array_key_last($dates)];

        return [
            'activity_days' => count($dates),
            'current_streak' => self::currentStreak($dates, $todayIso),
            'longest_streak' => self::longestStreak($dates),
            'last_activity_on' => $last,
        ];
    }

    /**
     * @param  list<string>  $sortedDates
     */
    private static function longestStreak(array $sortedDates): int
    {
        if ($sortedDates === []) {
            return 0;
        }

        $longest = 1;
        $run = 1;
        for ($i = 1, $n = count($sortedDates); $i < $n; $i++) {
            $prev = CarbonImmutable::parse($sortedDates[$i - 1])->startOfDay();
            $current = CarbonImmutable::parse($sortedDates[$i])->startOfDay();
            if ($prev->addDay()->equalTo($current)) {
                $run++;
                $longest = max($longest, $run);
            } else {
                $run = 1;
            }
        }

        return $longest;
    }

    /**
     * @param  list<string>  $sortedDates
     */
    private static function currentStreak(array $sortedDates, string $todayIso): int
    {
        if ($sortedDates === []) {
            return 0;
        }

        $today = CarbonImmutable::parse($todayIso)->startOfDay();
        $last = CarbonImmutable::parse($sortedDates[array_key_last($sortedDates)])->startOfDay();
        if ($last->diffInDays($today) > 1) {
            return 0;
        }

        $expect = $last;
        $streak = 0;
        for ($i = count($sortedDates) - 1; $i >= 0; $i--) {
            $day = CarbonImmutable::parse($sortedDates[$i])->startOfDay();
            if (! $day->equalTo($expect)) {
                break;
            }
            $streak++;
            $expect = $expect->subDay();
        }

        return $streak;
    }

    /**
     * @param  list<string>  $isoDates
     * @return list<array{date: string, label: string, played: bool}>
     */
    public static function recentDays(array $isoDates, string $todayIso, int $days = 7): array
    {
        $played = array_fill_keys($isoDates, true);
        $today = CarbonImmutable::parse($todayIso)->startOfDay();
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $today->subDays($i);
            $iso = $day->toDateString();
            $out[] = [
                'date' => $iso,
                'label' => self::WEEKDAY_LABELS[(int) $day->dayOfWeek],
                'played' => isset($played[$iso]),
            ];
        }

        return $out;
    }
}

<?php

namespace App\Domain\GameScoring;

/**
 * Lotki w wizycie X01 / Catch 40 (per-dart). null = tryb sumy, nie zgadujemy sektorów.
 */
final class VisitDartPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function validationRules(string $prefix = 'darts'): array
    {
        return [
            $prefix => 'nullable|array|max:3',
            $prefix.'.*.sector' => 'nullable|integer|min:0|max:25',
            $prefix.'.*.points' => 'nullable|integer|min:0|max:60',
            $prefix.'.*.label' => 'nullable|string|max:16',
            $prefix.'.*.remainingBefore' => 'nullable|integer|min:0|max:1001',
            $prefix.'.*.bust' => 'nullable|boolean',
        ];
    }

    /**
     * @return list<array{sector: int, points: int, label: string|null, remainingBefore: int|null, bust: bool}>|null
     */
    public static function normalize(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $out = [];
        foreach (array_slice(array_values($raw), 0, 3) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = isset($row['label']) ? (string) $row['label'] : null;
            $out[] = [
                'sector' => self::resolveSector($row, $label),
                'points' => (int) ($row['points'] ?? 0),
                'label' => $label !== '' ? $label : null,
                'remainingBefore' => isset($row['remainingBefore'])
                    ? (int) $row['remainingBefore']
                    : (isset($row['remaining_before']) ? (int) $row['remaining_before'] : null),
                'bust' => (bool) ($row['bust'] ?? false),
            ];
        }

        return $out === [] ? [] : $out;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function resolveSector(array $row, ?string $label): int
    {
        if (array_key_exists('sector', $row) && $row['sector'] !== null && $row['sector'] !== '') {
            $sector = (int) $row['sector'];
            if ($sector === 25 || ($sector >= 0 && $sector <= 20)) {
                return $sector;
            }
        }

        return self::sectorFromLabel($label);
    }

    public static function sectorFromLabel(?string $label): int
    {
        if ($label === null || $label === '') {
            return 0;
        }
        $raw = trim($label);
        $upper = strtoupper($raw);
        if (in_array($upper, ['0', 'MISS', 'OUT', '-'], true)) {
            return 0;
        }
        if (in_array($upper, ['BULL', 'DB', 'SB', 'B', '25', '50'], true)) {
            return 25;
        }
        if (preg_match('/^[SDT]?(\d{1,2})$/i', $raw, $m) === 1) {
            $n = (int) $m[1];
            if ($n >= 1 && $n <= 20) {
                return $n;
            }
            if ($n === 25) {
                return 25;
            }
        }

        return 0;
    }
}

<?php

namespace App\Domain\Career;

/**
 * Zamknięty katalog trybów, które trener może polecić.
 * LLM nie wymyśla nowych id — tylko wybiera z tej listy.
 */
final class CoachModeCatalog
{
    public const X01 = 'x01';

    public const BOB27 = 'bob27';

    public const CATCH40 = 'catch40';

    public const ATC = 'atc';

    public const CRICKET = 'cricket';

    public const CRICKET56 = 'cricket56';

    /**
     * @return list<array{id: string, label: string, trains: list<string>}>
     */
    public static function all(): array
    {
        return [
            [
                'id' => self::X01,
                'label' => 'X01',
                'trains' => ['średnia 3-dartowa', 'double-out (tylko per-dart)'],
            ],
            [
                'id' => self::BOB27,
                'label' => 'Bob’s 27',
                'trains' => ['duble D1–D20', 'bull'],
            ],
            [
                'id' => self::CATCH40,
                'label' => 'Catch 40',
                'trains' => ['checkout 61–100'],
            ],
            [
                'id' => self::ATC,
                'label' => 'Around the Clock',
                'trains' => ['tarcza', 'tempo'],
            ],
            [
                'id' => self::CRICKET,
                'label' => 'Cricket',
                'trains' => ['triple', 'wycinek tarczy'],
            ],
            [
                'id' => self::CRICKET56,
                'label' => 'Cricket 60',
                'trains' => ['triple', 'wycinek tarczy', 'bull'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function ids(): array
    {
        return array_column(self::all(), 'id');
    }

    public static function has(string $id): bool
    {
        return in_array($id, self::ids(), true);
    }
}

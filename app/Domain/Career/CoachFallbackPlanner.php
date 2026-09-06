<?php

namespace App\Domain\Career;

/**
 * Szablonowy plan, gdy nie ma modelu. Nie jest „żywym trenerem” — tylko poprawnym CoachPlan.
 */
final class CoachFallbackPlanner
{
    public static function plan(CoachDigest $digest): CoachPlan
    {
        $hints = $digest->focusHints;
        $weakest = $digest->weakestDouble;

        if (in_array('insufficient_data', $hints, true) || ! $digest->ready) {
            return self::collectData($digest);
        }

        if (self::hasAny($hints, ['doubles_weak', 'doubles_dropping', 'bob27_sector'])) {
            return self::doublesFocus($weakest);
        }

        if (self::hasAny($hints, ['x01_dropping', 'x01_flat'])) {
            return self::scoringFocus();
        }

        return self::balanced();
    }

    private static function collectData(CoachDigest $digest): CoachPlan
    {
        $needsDoubles = ! $digest->hero['hasDoubles'];

        return CoachPlan::fromArray([
            'source' => CoachPlan::SOURCE_TEMPLATE,
            'headline' => 'Za mało danych na konkretną radę',
            'focus' => 'Zbierz paliwo pod kolejną sesję',
            'cards' => [
                self::card(
                    30,
                    $needsDoubles ? CoachModeCatalog::BOB27 : CoachModeCatalog::X01,
                    $needsDoubles ? 'Duble' : 'Punktacja',
                    $needsDoubles
                        ? 'Bob’s 27 liczy każdą lotkę w double — najszybszy sposób, żeby wykres dubli przestał być pusty.'
                        : 'Krótki X01 per-dart da średnią i pierwsze próby na double.',
                    $needsDoubles
                        ? ['Zagraj jedną rundę Bob’s 27.', 'Celuj spokojnie, nie goni wyniku.', 'Po sesji sprawdź, które duble były najsłabsze.']
                        : ['Zagraj krótki X01, każdy rzut osobno.', 'Gdy zostaje double, celuj w nie świadomie.', 'Nie poprawiaj średniej na siłę — licz lotki uczciwie.'],
                ),
                self::card(
                    60,
                    CoachModeCatalog::X01,
                    'Rytm X01',
                    'Godzina X01 buduje zarówno średnią, jak i (w per-dart) duble.',
                    ['Ustaw 501 per-dart.', 'Graj do końca legów, bez restartu po złym starcie.', 'Notuj, przy jakim remaining najczęściej pudłujesz checkout.'],
                ),
                self::card(
                    90,
                    CoachModeCatalog::ATC,
                    'Tarcza',
                    'Around the Clock rozgrzewa całą tarczę, gdy jeszcze nie wiadomo, co kuleje.',
                    ['Jedna pełna pętla 1–20 + bull.', 'Tempo równe, bez gonienia zegara.', 'Drugą pętlę zagraj wolniej i celniej.'],
                ),
            ],
        ]);
    }

    private static function doublesFocus(?string $weakest): CoachPlan
    {
        $target = $weakest ?? 'słabsze duble';
        $why30 = $weakest !== null
            ? 'W Bob’s 27 najsłabszy sektor w oknie to '.$weakest.' — tam idzie pierwsza sesja.'
            : 'Duble w oknie są słabe albo spadają — Bob’s 27 to najczystszy trening dubli.';

        return CoachPlan::fromArray([
            'source' => CoachPlan::SOURCE_TEMPLATE,
            'headline' => 'Najpierw duble',
            'focus' => 'Skuteczność na double',
            'cards' => [
                self::card(
                    30,
                    CoachModeCatalog::BOB27,
                    'Bob’s 27',
                    $why30,
                    [
                        'Zagraj Bob’s 27 od D1.',
                        'Przy '.$target.' zwolnij i ustaw się od nowa.',
                        'Nie nadrabiaj pudła następną lotką na siłę.',
                    ],
                ),
                self::card(
                    60,
                    CoachModeCatalog::X01,
                    'Checkout per-dart',
                    'X01 per-dart przenosi duble z ćwiczenia na realny remaining.',
                    ['501, każdy rzut osobno.', 'Od remaining 170 myśl o ścieżce na double.', 'Pudło na double: ten sam double, nie inny sektor.'],
                ),
                self::card(
                    90,
                    CoachModeCatalog::BOB27,
                    'Pełna tarcza dubli',
                    'Dłuższa sesja Bob’s 27 utrwala słabe sektory bez gonienia średniej X01.',
                    ['Dwie rundy Bob’s 27.', 'Drugą rundę zacznij od najsłabszego dubla z pierwszej.', 'Zakończ 9 lotkami w bull.'],
                ),
            ],
        ]);
    }

    private static function scoringFocus(): CoachPlan
    {
        return CoachPlan::fromArray([
            'source' => CoachPlan::SOURCE_TEMPLATE,
            'headline' => 'Średnia stoi albo spada',
            'focus' => 'Punktacja X01',
            'cards' => [
                self::card(
                    30,
                    CoachModeCatalog::X01,
                    'T20 i rytm',
                    'Krótki X01 bez presji checkoutu — najpierw treble, potem double.',
                    ['Kilka legów 501.', 'Pierwsze dwie lotki w T20/T19, trzecia zostaje.', 'Nie kończ lega na siłę kosztem ustawienia.'],
                ),
                self::card(
                    60,
                    CoachModeCatalog::CATCH40,
                    'Wyjścia 61–100',
                    'Catch 40 trenuje setup do checkoutu, gdy średnia nie idzie w górę od samych 501.',
                    ['Jedna pełna runda Catch 40.', 'Każdy out kończ dublem, nie singlem.', 'Po pudle wróć do tego samego outu.'],
                ),
                self::card(
                    90,
                    CoachModeCatalog::CRICKET56,
                    'Triple na wycinku',
                    'Cricket 60 wymusza treble na kolejnych sektorach — to paliwo pod średnią.',
                    ['Pełna runda 15–bull.', 'Celuj T, nie S, dopóki T jest w grze.', 'Bull na spokojnie, bez pośpiechu z wcześniejszych rund.'],
                ),
            ],
        ]);
    }

    private static function balanced(): CoachPlan
    {
        return CoachPlan::fromArray([
            'source' => CoachPlan::SOURCE_TEMPLATE,
            'headline' => 'Utrzymaj formę, zaostrz słabszy element',
            'focus' => 'Równowaga punktacji i dubli',
            'cards' => [
                self::card(
                    30,
                    CoachModeCatalog::BOB27,
                    'Przypomnienie dubli',
                    'Krótki Bob’s 27 utrzymuje duble, gdy reszta okna wygląda równo.',
                    ['Jedna runda Bob’s 27.', 'Bez gonienia wyniku.', 'Zwróć uwagę na dwa najsłabsze duble.'],
                ),
                self::card(
                    60,
                    CoachModeCatalog::X01,
                    'Meczowe 501',
                    'Godzina X01 per-dart trzyma średnią i checkout w jednym rytmie.',
                    ['Kilka pełnych legów 501.', 'Graj jak w meczu: bez restartu po 26.', 'Ostatni leg — świadomy checkout.'],
                ),
                self::card(
                    90,
                    CoachModeCatalog::CRICKET,
                    'Wycinek i triple',
                    'Cricket uzupełnia X01 o treble na 20/19/18, których Bob’s 27 nie trenują.',
                    ['Jeden mecz Cricket.', 'Zamykaj 20 i 19 zanim pójdziesz w punkty.', 'Nie zbieraj punktów z otwartych liczb przeciwnika na siłę.'],
                ),
            ],
        ]);
    }

    /**
     * @param  list<string>  $steps
     * @return array{durationMin: int, modeId: string, focus: string, why: string, steps: list<string>}
     */
    private static function card(int $durationMin, string $modeId, string $focus, string $why, array $steps): array
    {
        return [
            'durationMin' => $durationMin,
            'modeId' => $modeId,
            'focus' => $focus,
            'why' => $why,
            'steps' => $steps,
        ];
    }

    /**
     * @param  list<string>  $hints
     * @param  list<string>  $needles
     */
    private static function hasAny(array $hints, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (in_array($needle, $hints, true)) {
                return true;
            }
        }

        return false;
    }
}

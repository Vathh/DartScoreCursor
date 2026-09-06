# Kariera gracza — kontrakt statystyk

Źródło prawdy produktowej: [`product.md`](product.md). Ten plik spina **okna, źródła, snapshoty** i **zestaw zbieranych metryk per `gameType`**.

Trener osobisty (kontrakt, bez UI): [`design_player_coach.md`](design_player_coach.md).

Widok profilu (które pola pokazujemy) jest **osobną decyzją** — tu tylko to, co zapisujemy z ukończonej gry.

## Źródła (chipy UI)

- **Turnieje** — turniej klubowy + liga piramidowa (`source` w bazie: `tournament` | `league`).
- **Szybkie gry** — `quick`.
- **Treningi** — tylko własny profil (`training`), slot **JA**.
- **Wszystkie źródła** — suma widocznych.

Obcy profil: bez treningu. Goście (`user_id` null): bez kariery.

Źródło **nie** zmienia zestawu metryk. Wyjątek X01 turniej/liga: dodatkowo pełny log wizyt (`game_visits`). Tryb urządzenia (`one_device` / `each_own`) nie zmienia zestawu.

## Okna (toczące się, Europe/Warsaw)

30 / 90 / 180 / 365 dni wstecz od początku dzisiaj + **Całość**.

Domyślnie: Wszystkie źródła + 3 mies.

## Snapshot (`player_game_snapshots`)

Jeden wiersz = jedna ukończona sesja zarejestrowanego gracza.

- `source`, `game_type`, `occurred_at`, `client_uuid`, morph do meczu
- `metrics` JSON z `schema_version` (aktualnie **2**). Nowe pola dokładamy w JSON **bez migracji kolumn**. Nieznane klucze są dozwolone (rozszerzalność).

Walkower / brak danych do metryk → brak snapshotu.

Dwie warstwy:

- **Log meczu** — wizyty / lotki / `dartLog` (+ `matchLog` przez nogi).
- **Snapshot** — zagregowany JSON per gracza, liczony kolektorem Domain per `gameType`.

## Zbieranie per `gameType`

301 i 501 to ten sam `x01`. Cricket 60 w kodzie: `cricket56`.

### X01 (`x01`)

- `average` — średnia 3-dartowa, ważona lotkami. Bust: 0 pkt, 3 lotki. Checkout: fizyczne lotki.
- `darts_thrown`, `points`
- `visit_scores` — kubełki rozłączne: `180` (dokładnie 180), `170` (170–179), `140` (140–169), `100` (100–139), `80` (80–99), `60` (60–79)
- `double_attempts` / `double_successes` — **tylko per-dart** (remaining 2–40 parzyste albo 50). Suma wizyty: `double_tracked = false`
- `best_leg_darts` — najmniej lotek, którymi ten gracz zamknął lega
- `closed_legs`: `[{darts}]` — zamknięcia tego gracza
- `checkouts`: `[{score, darts}]` — wszystkie checkouty, dowolna wartość
- `sectors` — zliczenia **1–20 + bull (25) + pudło (0)**, bez S/D/T. **Tylko per-dart.** Przy sumie trzech rzutów `sectors` = `null` (nie zgadujemy).

Turniej i liga: pełny log `game_visits` (już jest). Przy per-dart dodatkowo JSON `darts` na wizycie: `{sector, points, label, remainingBefore}`.

### Cricket (`cricket`) — quick + trening

- `darts_thrown`
- `marks` per 15–20 + bull (T20 = 3)
- `points` per sektor (punkty cricketowe)
- `hits` per sektor — ile **lotek** wylądowało w sektorze (bez S/D/T)
- `win_darts` — lotki zwycięzcy do wygrania gry; przegrany: `null`

### Bob's 27 (`bob27`) — quick + trening

- `double_attempts` / `double_successes` (każda lotka)
- `bob27_mode` (`hard` / `easy`), `bob27_bull` (`with` / `without`)
- `score`, `finished`
- `ended_at_target` — gdy nie skończył: `D1`…`D20` / `Bull`; gdy skończył: `null`
- `per_double` zostaje (rozszerzalność)

Personal best (profil później): najdalszy etap; gdy `finished` — najwyższy `score`.

### Around the Clock (`atc`) — quick + trening

- `darts_thrown`
- per sektor 1–20 + bull: `{successes, attempts}`

### Catch 40 (`catch40`) — quick + trening

Mobile **wymusza per-dart** na ekranie Catch 40 (globalne SUM/PER_DART bez zmian).

- `double_attempts` / `double_successes`
- `darts_thrown`, `score` (max 120)
- `outs`: 40 pozycji 61–100, `{out, darts}` — `darts: null` przy niepowodzeniu (X)

### Cricket 60 (`cricket56`) — quick + trening

- `score` (max 60)
- **bez** `darts_thrown` (zawsze 21 przy dokończonej grze)
- per sektor 15–20 + bull: `{S, D, T}` (bull: outer = S, inner = D, T = 0)

Wizyta niesie `marks` (3 lotki), nie tylko sumę punktów.

## Agregacja v1 na profilu (na razie)

Nadal: średnia X01 (ważona lotkami) oraz % double. Pusty stan, nie 0%. Reszta zestawów czeka na widok profilu.

## Trening

Zalogowany: w składzie pozycja `{nazwa} – JA` (`player_id` konta). Auto-dodanie tylko przy **pustym** składzie. Usunięty JA zostaje usunięty (pamiętany skład). Na konto idzie tylko slot JA. Opt-out: grać pod lokalnym imieniem.

Historia treningów lokalnych **nie** jest importowana wstecz.

Mobile składa ten sam kształt `metrics`; backend normalizuje i **nie odrzuca** nieznanych kluczy.

## Historia na profilu

Obcy: quick + turniej + liga. Własny: plus trening JA (v1 nieklikalny).

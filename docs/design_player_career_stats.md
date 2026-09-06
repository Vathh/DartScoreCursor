# Kariera gracza — kontrakt statystyk

Źródło prawdy produktowej: [`product.md`](product.md). Ten plik spina **okna, źródła, duble i snapshoty**.

Trener osobisty (kontrakt, bez UI): [`design_player_coach.md`](design_player_coach.md).

## Źródła (chipy UI)

- **Turnieje** — turniej klubowy + liga piramidowa (`source` w bazie: `tournament` | `league`).
- **Szybkie gry** — `quick`.
- **Treningi** — tylko własny profil (`training`), slot **JA**.
- **Wszystkie źródła** — suma widocznych.

Obcy profil: bez treningu. Goście (`user_id` null): bez kariery.

## Okna (toczące się, Europe/Warsaw)

30 / 90 / 180 / 365 dni wstecz od początku dzisiaj + **Całość**.

Domyślnie: Wszystkie źródła + 3 mies.

## Metryki v1

- Średnia 3-dartowa: tylko X01, ważona lotkami `(suma punktów / suma lotek) * 3`. Bust: 0 pkt, 3 lotki. Checkout: fizyczne lotki.
- Duble: `double_attempts` / `double_successes`. X01 tylko per-dart (remaining 2–40 lub 50). Bob’s 27: każda lotka. Suma wizyty X01: nie liczymy.

Wykresy: średnia X01 oraz % double. Pusty stan, nie 0%.

## Snapshot (`player_game_snapshots`)

Jeden wiersz = jedna ukończona sesja zarejestrowanego gracza.

- `source`, `game_type`, `occurred_at`, `client_uuid`, morph do meczu
- `metrics` JSON: `average`, `darts_thrown`, `points`, `double_attempts`, `double_successes`, `double_tracked`; Bob27 dodatkowo `per_double` (zapis, bez UI v1)

Walkower / brak wizyt → brak snapshotu.

## Trening

Zalogowany: w składzie pozycja `{nazwa} – JA` (`player_id` konta). Auto-dodanie tylko przy **pustym** składzie. Usunięty JA zostaje usunięty (pamiętany skład). Na konto idzie tylko slot JA. Opt-out: grać pod lokalnym imieniem.

Historia treningów lokalnych **nie** jest importowana wstecz.

## Historia na profilu

Obcy: quick + turniej + liga. Własny: plus trening JA (v1 nieklikalny).

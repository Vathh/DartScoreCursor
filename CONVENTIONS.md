# Konwencje i ustalenia projektowe

## Nazewnictwo - Game vs Match

**WAŻNE:** W projekcie zawsze używamy słowa **Game** zamiast **Match** do określania rozgrywek.

### Powód
Słowo `match` jest słowem kluczowym w języku PHP, dlatego unikamy jego używania dla zachowania czystości kodu i uniknięcia potencjalnych konfliktów.

### Zasady
- ✅ Używamy: `Game`, `GameLeg`, `GameService`, `QuickGame`, `GroupGame`, `PlayoffGame`
- ❌ Unikamy: `Match`, `MatchLeg`, `MatchService` (z wyjątkiem przypadków gdzie jest to konieczne w kontekście PHP)

### Przykłady
- `GameLeg` zamiast `MatchLeg`
- `GameLegService` zamiast `MatchLegService`
- `GameLegDTO` zamiast `MatchLegDTO`
- Tabela w bazie: `game_legs` zamiast `match_legs`

## Architektura

### Domain-Repository-Service Pattern
Projekt ścisłe przestrzega wzorca Domain-Repository-Service:
- **Domain**: Logika biznesowa, encje domenowe
- **Repository**: Dostęp do danych, operacje na bazie
- **Service**: Orkiestracja, koordynacja między warstwami

### Struktura katalogów
```
app/
├── Domain/          # Encje domenowe
├── Repositories/    # Warstwa dostępu do danych
├── Services/        # Warstwa logiki biznesowej
├── DTO/            # Data Transfer Objects
├── Models/         # Eloquent models
└── Http/
    └── Controllers/ # Kontrolery API i web
```

## Użytkownik a gracz (Player)

- **Każdy zarejestrowany użytkownik (`User`) ma dokładnie jeden powiązany rekord `Player`** (`players.user_id` jest unikalne). Nie zakładamy istnienia konta bez gracza.
- **Rejestracja** (API: `App\Http\Controllers\Api\AuthController::register`, web: `App\Http\Controllers\AuthController::register`) tworzy najpierw `User`, potem `Player` z wybraną nazwą — tak samo należy postępować w **seedach i testach**, gdy tworzysz użytkownika „jak po rejestracji”.
- **Goście** turniejowi / ligowi to `Player` z `user_id = null`; dotyczy to tylko gości, nie kont użytkowników.

## Turniej a schematy punktów (`PointScheme`)

- **Liczba uczestników** przy starcie (`TournamentService::tryCreateGroupGames`, `count($playerIds)`) musi wpadać w co najmniej jeden przedział `min_players`–`max_players` w `point_schemes` (obecnie seed pokrywa **4–80**). Dobór: `PointSchemeService::findByPlayersAmount`; przy braku dopasowania — wyjątek.
- **Reguły** w `point_scheme_rules` opisują punkty za miejsca w grupie oraz za etapy drabinki (`EIGHT`, `QUARTER`, `THIRD`, `FINAL`). **Większy turniej = wyższa skala punktów** przy tym samym etapie (porównaj np. zwycięstwo w finale między przedziałami w seedzie).
- Przedziały w seedzie są **rozłączne** (4–8, 9–16, …, aż do 73–80). Jeśli kiedyś dodasz nakładające się zakresy, `PointSchemeService::findByPlayersAmount` wybiera schemat z **największym `min_players`** (wyższa skala przy granicy).
- **Źródło prawdy** i zmiany przedziałów / liczb: `database/seeders/PointSchemeSeeder.php` — nowe przedziały tylko po uzgodnieniu; pilnuj monotoniczności i spójności z kodem wyników.

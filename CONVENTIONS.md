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

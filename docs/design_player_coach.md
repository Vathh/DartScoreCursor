# Trener osobisty — kontrakt (bez UI)

Źródło kariery: [`design_player_career_stats.md`](design_player_career_stats.md).  
Ten plik spina **digest → plan**. Nie ma ekranu, przycisku ani trasy HTTP — kod jest na później.

## Co to jest

Z tych samych liczb co dashboard (okna, źródła, średnia X01, duble, `per_double` Bob’s 27) trener ma kiedyś powiedzieć:

- co się zmieniło,
- **jedną** rzecz do poprawy,
- karty sesji **30 / 60 / 90 min** z zamkniętego katalogu trybów.

LLM (później) = głos + wybór karty. **Nie** sędzia liczb. Wejście: digest JSON. Wyjście: wymuszony `CoachPlan`, walidowany schematem. Bez klucza / pad modelu: fallback szablonowy (`source: template`).

## Stan wdrożenia

| Warstwa | Jest? |
|---------|--------|
| Domain: katalog, digest, plan, walidacja, fallback | tak |
| `PlayerCoachService::buildForOwner` | tak |
| Klient LLM / klucz `.env` | **nie** |
| `POST /api/me/coach` | **nie** |
| UI web / mobile | **nie** |

Tylko własna kariera (trening włącznie). Gość (`user_id` null) → brak trenera. Obcy profil nigdy nie woła tego serwisu.

## Digest (wejście modelu)

`CoachDigestBuilder` składa fakty ze snapshotów. **Bez PII** (brak imienia, `player_id`, maila) i **bez surowych wizyt**.

```json
{
  "schemaVersion": 1,
  "window": "90d",
  "generatedAt": "…",
  "ready": true,
  "notReadyReasons": [],
  "hero": {
    "games": 12,
    "x01Average": 55.68,
    "x01AverageDelta": -1.2,
    "doublePct": 31.0,
    "doublePctDelta": -4.5,
    "doubleAttempts": 40,
    "doubleSuccesses": 12,
    "doubleLabel": "12/40",
    "hasX01": true,
    "hasDoubles": true
  },
  "sources": { "training": 4, "quick": 5, "tournament": 3 },
  "gameTypes": { "x01": 9, "bob27": 3 },
  "bob27": {
    "perDouble": { "D16": { "attempts": 12, "successes": 1, "pct": 8.3 } },
    "weakest": "D16",
    "weakestPct": 8.3
  },
  "focusHints": ["doubles_weak", "bob27_sector"],
  "modes": [{ "id": "bob27", "label": "Bob’s 27", "trains": ["duble D1–D20", "bull"] }]
}
```

`focusHints` to kody maszynowe, nie zdania: `insufficient_data`, `doubles_weak`, `doubles_dropping`, `x01_dropping`, `x01_flat`, `bob27_sector`, `steady`.

**Ready:** co najmniej 3 snapshoty w oknie **albo** ≥ 9 prób na double; oraz jest X01 albo duble. Inaczej fallback „zbierz dane”, nie zmyślona diagnoza.

Domyślne okno serwisu: **90d**, wszystkie źródła (w tym trening JA).

## Plan (wyjście)

`CoachPlan::fromArray` odrzuca halucynacje.

- `source`: `template` | `llm`
- `headline`, `focus` — niepuste, limity znaków
- `cards`: 1–3, **unikalne** `durationMin` ∈ {30, 60, 90}
- karta: `modeId` **tylko** z `CoachModeCatalog`, `why`, `steps` 1–6

Katalog: `x01`, `bob27`, `catch40`, `atc`, `cricket`, `cricket56`.

Dziś jedyny producent planu to `CoachFallbackPlanner` (szablon). Przyszły klient LLM ma zwracać ten sam JSON i przejść przez `CoachPlan::fromArray`.

## Później (nie teraz)

1. Klucz tylko na backendzie, timeout, cache per gracz+okno.
2. `POST /api/me/coach` — wyłącznie zalogowany właściciel.
3. Przycisk na **własnym** profilu, opt-in.
4. Model: mały (Flash / Groq / mini) + wymuszony JSON.

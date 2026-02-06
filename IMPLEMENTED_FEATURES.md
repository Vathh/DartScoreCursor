# Zaimplementowane funkcjonalności – API / aplikacja webowa (DartScore)

Dokument opisuje dotychczas zaimplementowane funkcje backendu (Laravel) oraz aplikacji webowej. Przydatny przy planowaniu kolejnych kroków.

---

## 1. Autentykacja

- **Rejestracja** – POST `/register` (API), strona `/register` (web).
- **Logowanie** – POST `/login` (API – token dla aplikacji mobilnej), POST `/login` (web – sesja).
- **Wylogowanie** – POST `/logout` (web).
- **Logowanie do turnieju** – POST `/api/login` (kod turnieju, zwraca token + `tournamentId`).

---

## 2. Użytkownicy i gracze

- **Gracze zarejestrowani vs goście**  
  Gracze z `user_id` = zarejestrowani (profil, wyszukiwarka, znajomi). Gracze bez `user_id` = goście (np. do turniejów).
- **Wyszukiwarka graczy** (web) – `/players/search?q=...` – tylko gracze zarejestrowani.
- **Profil gracza** (web) – `/players/{id}` – tylko dla zarejestrowanych; dla gościa zwracane 404.
  - **Zakładki:** Przegląd (statystyki quick + turniejowe), **Historia meczów** (ostatnie mecze: szybkie, grupa, play-off; 5 na stronę, przycisk „Załaduj więcej” ładuje kolejne 5 przez AJAX).
- **Historia meczów** – GET `/players/{player}/matches?page=...` zwraca JSON `{ items, has_more }` (dla „Załaduj więcej”).
- **Dodawanie do znajomych** (web) – POST `/players/{player}/add-friend` (po zalogowaniu).

---

## 3. Statystyki gracza

- **Tabela cache** `player_stats` – jedna linia na gracza (statystyki quick + turniejowe).
- **Odczyt w profilu** – statystyki quick i turniejowe z cache; przy braku wpisu – przeliczenie i zapis.
- **Aktualizacja cache** – po zapisie wyników szybkiego meczu (`QuickGameService::updateResults`) oraz po zakończeniu meczu grupowego/play-off (`GameService`).
- **Warstwa danych** – `PlayerStatRepository` (dostęp do bazy), `PlayerStatsService` (orkiestracja, zgodnie z CONVENTIONS).

---

## 4. Znajomi (Friends)

- **API (auth:sanctum):**
  - GET `/api/friends` – lista znajomych.
  - POST `/api/friends/add` – dodaj znajomego.
  - DELETE `/api/friends/remove` – usuń znajomego.
  - POST `/api/friends/invite` – wyślij zaproszenie.
  - POST `/api/friends/accept`, POST `/api/friends/reject` – akceptacja/odrzucenie zaproszenia.
  - GET `/api/friends/invitations/received`, GET `/api/friends/invitations/sent` – zaproszenia.
  - GET `/api/users/search` – wyszukiwanie użytkowników (np. do zaproszeń).
- **Tabele:** `friendships`, `friendship_invitations`.

---

## 5. Ligi i sezony (web)

- **Ligi** – CRUD, powiązani użytkownicy, administratorzy, goście (lista, dodawanie, usuwanie).
- **Podgląd ligi** – `/leagues/{id}`: opis, sezony oraz **tabela wyników ligi (top 40)**.
  - **Tabela wyników** – 40 zawodników z największą sumą punktów z meczów turniejowych w historii ligi (wszystkie sezony). Kolumny: Miejsce, Zawodnik, Punkty, 180 (max), 170+, QF, HF, Najniższa lotka (QF), Najwyższy finish (HF). Link do profilu gracza.
  - **Cache** – tabela `league_player_stats` (league_id, player_id, points, count_max, count_170_plus, count_qf, count_hf, best_qf, best_hf). Aktualizacja po zakończeniu meczu grupowego/play-off (`GameService` → `LeagueStatsService::recalculateForLeague`). Przy pierwszym wejściu w podgląd ligi – przeliczenie gdy brak wpisów.
- **Sezony** – CRUD, powiązani użytkownicy, administratorzy, goście.
- **Strony:** `/leagues`, `/leagues/{id}/relatedUsers`, `/leagues/{id}/admins`, `/leagues/{id}/guests` (analogicznie dla `/seasons`).

---

## 6. Turnieje (web + API)

- **CRUD turniejów** (web) – `/tournaments`.
- **Uruchomienie turnieju** – `/tournaments/{id}/start`, `/tournaments/{id}/run`.
- **Widok turnieju** – grupy, play-off, wyniki, osiągnięcia (achievements).
- **API (auth:sanctum):**
  - GET `/api/game/active?tournamentId=...` – aktywne mecze turniejowe.
  - POST `/api/game/inProgress` – ustaw status „w trakcie”.
  - POST `/api/game/update` – zapis wyniku meczu grupowego lub play-off (legi, achievementy).

---

## 7. Szybkie mecze (Quick Game)

- **Klasyczny flow (2 graczy, zarejestrowani):**
  - POST `/api/quick-game/create` – utworzenie meczu (auth).
  - GET `/api/quick-game/active` – aktywne szybkie mecze użytkownika.
  - POST `/api/quick-game/inProgress` – ustaw status „w trakcie”.
  - POST `/api/quick-game/update` – zapis wyniku (legi, achievementy; może być bez auth przy graczach tymczasowych).

- **Nowy flow – lobby (2–6 graczy, z kodem):**
  - POST `/api/quick-game/lobby/create` – utworzenie lobby (auth).
  - GET `/api/quick-game/lobby/code/{code}` – pobranie lobby po kodzie (bez auth).
  - POST `/api/quick-game/lobby/join` – dołączenie po kodzie (bez auth – gość podaje nazwę).
  - GET `/api/quick-game/lobby/{id}` – szczegóły lobby (auth).
  - POST `/api/quick-game/lobby/{id}/join` – dołączenie po ID (auth).
  - POST `/api/quick-game/lobby/{id}/leave` – opuszczenie lobby.
  - POST `/api/quick-game/lobby/{id}/ready` – gotowość.
  - POST `/api/quick-game/lobby/{id}/start` – start meczu (host).
  - POST `/api/quick-game/lobby/{id}/invite` – zaproszenie znajomego do lobby.
  - POST `/api/quick-game/update` – zapis wyników szybkiego meczu (lista graczy: wyniki, średnie, achievementy dla zarejestrowanych).

- **Tabele:** `quick_games`, `quick_game_results`, `quick_game_lobbies`, `quick_game_lobby_players`.

---

## 8. Mecze turniejowe – legi i achievementy

- **Game legs** – tabela `game_legs` (powiązanie z `games` lub `playoff_games` lub `quick_games`), zapis średnich per gracz w legu.
- **Achievementy** – tabela `achievements` (max, 170+, HF, QF; `tournament_id` null = quick, nie null = turniej).
- **Zapis** – przez `GameService` (mecze grupowe/play-off) i `QuickGameService` (szybkie mecze).

---

## 9. Architektura (CONVENTIONS.md)

- **Domain–Repository–Service:** Repository = dostęp do danych, Service = orkiestracja.
- **Nazewnictwo:** Game (nie Match), GameLeg, QuickGame itd.
- **Gracze:** tylko zarejestrowani w wyszukiwarce i profilu; goście używani głównie w turniejach i lobby.

---

## 10. Testy

- **Feature:** Auth, Friendship, Game, QuickGame, League, Season, Tournament.
- **Manual:** skrypty i scenariusze szybkiego meczu w `tests/Manual/`.

---

## Co można planować dalej (przykłady)

- Rozszerzenie profilu gracza (historia meczów, wykresy).
- Powiadomienia (zaproszenia do znajomych, do lobby).
- Eksport wyników / raporty.
- Panel admina (np. zarządzanie użytkownikami).
- Dodatkowe statystyki w `player_stats` lub widoki.

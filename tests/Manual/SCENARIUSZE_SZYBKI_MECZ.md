# Scenariusze testowe: Szybki mecz (backend + mobilka)

Poniższe scenariusze weryfikują, czy użytkownik może założyć lobby, dodać znajomych, rozpocząć mecz i czy backend z mobilką współgrają.

**Wymagania:** działający backend (Laravel), baza MySQL, aplikacja mobilna (Expo) z ustawionym w `apiConfig.js` adresem API (np. `http://10.0.2.2:8000/api` dla emulatora Android lub adres komputera w sieci).

---

## 1. Logowanie (tryb turnieju)

- Użytkownik w aplikacji wybiera **Turniej** lub **Szybki mecz**.
- **Szybki mecz** wymaga logowania (przekierowanie do logowania turniejowego).
- Po zalogowaniu użytkownik trafia na listę meczów; przycisk **Szybki mecz** prowadzi do lobby.

**API:** `POST /api/login` z `{ "code": "KOD_TURNIEJU" }` → `token`, `tournamentId`.

---

## 2. Założenie lobby

- Na ekranie **Szybki mecz – Lobby** użytkownik klika **Utwórz lobby**.
- Backend tworzy lobby i zwraca `lobby` z `id`, `code` (6 znaków), `host`, `players` (host jest pierwszym graczem).
- Aplikacja pokazuje **Kod lobby** i listę graczy (1/6).

**API:** `POST /api/quick-game/lobby/create`  
**Nagłówki:** `Authorization: Bearer {token}`  
**Oczekiwana odpowiedź:** 201, `{ "lobby": { "id", "code", "status": "waiting", "host", "players", "isHost": true, "currentPlayer" } }`.

---

## 3. Dołączenie drugiego gracza (znajomy po kodzie)

- Drugi użytkownik (lub ten sam na innym urządzeniu) wchodzi w **Szybki mecz** → **Dołącz po kodzie**.
- Wpisuje 6-znakowy kod i (opcjonalnie) przy logowaniu jako gość – swoją nazwę.
- Backend: `POST /api/quick-game/lobby/join` z `{ "code": "ABC123" }` lub `{ "code": "ABC123", "tempPlayerName": "Jan" }` (bez auth dla gościa).
- W lobby pojawiają się 2 gracze; pierwszy widzi przycisk **Gotowy**, host – **Rozpocznij mecz** (gdy jest ≥2 graczy).

**API:**  
- Z auth: `POST /api/quick-game/lobby/join`, body `{ "code": "..." }`, header `Authorization: Bearer {token}`.  
- Bez auth (gość): body `{ "code": "...", "tempPlayerName": "Nazwa" }`.

---

## 4. Panel znajomych – zaproszenie do lobby

- W lobby (jako zalogowany) użytkownik klika **Znajomi** (prawy górny róg).
- Otwiera się panel boczny z listą znajomych (`GET /api/friends`).
- Przy znajomym klika **Zaproś** → `POST /api/quick-game/lobby/{lobbyId}/invite` z `{ "friendId": userId }`.
- Backend zwraca komunikat z kodem lobby; znajomy może dołączyć po kodzie (scenariusz 3) lub w przyszłości przez powiadomienie.

**API:**  
- `GET /api/friends` → `{ "friends": [ { "id", "name", "playerId" } ] }`.  
- `POST /api/quick-game/lobby/{lobbyId}/invite`, body `{ "friendId": number }`.

---

## 5. Gotowość i start meczu

- Gracze w lobby ustawiają **Gotowy** (`POST /api/quick-game/lobby/{lobbyId}/ready` z `{ "isReady": true }`).
- Host klika **Rozpocznij mecz** gdy jest ≥2 graczy.
- Backend: `POST /api/quick-game/lobby/{lobbyId}/start` → ustawia `status: "in_progress"`, `started_at`.
- Aplikacja po otrzymaniu lobby ze statusem `in_progress` przekierowuje na ekran **Match** z parametrem `quickGame: { lobbyId, players }`.

**API:**  
- Ready: `POST .../ready`, body `{ "isReady": true }`.  
- Start: `POST .../start` (bez body).

---

## 6. Rozgrywka (2–6 graczy)

- Ekran **Match** pokazuje listę graczy (2–6), wybór „Kto rzuca pierwszy?”, licznik 501, klawiatura numeryczna.
- Kolejność rzutów: po wpisaniu wyniku i OK następny gracz (indeks `(currentPlayerIndex + 1) % N`).
- Wygrana w legu: pierwszy gracz, który zejdzie do zera (z potwierdzeniem checkout).
- Mecz kończy się, gdy którykolwiek gracz ma **2 wygrane legi**.

---

## 7. Wysłanie wyniku szybkiego meczu

- Po zakończeniu meczu aplikacja buduje payload tylko dla graczy **zarejestrowanych** (mających `playerId`).
- Wysyłane jest: `POST /api/quick-game/update` z ciałem:
  - `players`: tablica `{ playerId, score (legsWon), place (1,2,...), average, dartsThrown, pointsEarned }` (min. 2 graczy z `playerId`),
  - `achievements`: tablica `{ playerId, value, type }` (opcjonalnie),
  - `lobbyId`: id lobby (opcjonalnie).
- Backend tworzy wpis w `quick_games` (przez `createWithResults`), zapisuje wyniki w `quick_game_results` i achievementy.

**Zgodność z walidacją (QuickGameResultRequest):**  
- `players`: required, array, min:2, max:6; każdy element: `playerId` (required, exists:players), `score` (required, int, min:0), `place` (nullable), `average`, `dartsThrown`, `pointsEarned` (nullable).  
- Aplikacja wysyła dokładnie te pola; `lobbyId` jest pomijane w JSON gdy brak (undefined).

---

## 8. Opuszczenie lobby

- W lobby użytkownik klika **Opuść lobby**.
- Aplikacja: `POST /api/quick-game/lobby/{lobbyId}/leave`; dla gościa w body: `{ "tempPlayerName": "..." }`.
- Host opuszczający lobby powoduje usunięcie lobby po stronie backendu.

---

## Szybka weryfikacja API (curl)

Przy uruchomionym backendzie (`php artisan serve`) i działającej bazie:

```bash
# 1. Logowanie (użyj prawdziwego kodu z bazy login_codes)
curl -s -X POST http://127.0.0.1:8000/api/login -H "Content-Type: application/json" -d "{\"code\":\"KOD\"}"

# 2. Utworzenie lobby (wstaw TOKEN z kroku 1)
curl -s -X POST http://127.0.0.1:8000/api/quick-game/lobby/create -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json"

# 3. Lista znajomych
curl -s http://127.0.0.1:8000/api/friends -H "Authorization: Bearer TOKEN"

# 4. Dołączenie po kodzie (wstaw CODE z kroku 2)
curl -s -X POST http://127.0.0.1:8000/api/quick-game/lobby/join -H "Content-Type: application/json" -d "{\"code\":\"CODE\"}"
```

---

## Uwagi

- **Testy PHPUnit** (QuickGameApiTest, FriendshipApiTest) wymagają **działającej bazy MySQL** (np. `dartscore_test`). Błąd „Nie można nawiązać połączenia” oznacza, że serwer MySQL nie jest uruchomiony lub dane w `phpunit.xml` / `.env.testing` są nieprawidłowe.
- Aplikacja mobilna **nie** używa już `POST /api/game/update` dla szybkich meczów z lobby – tylko `POST /api/quick-game/update` z listą `players` i opcjonalnie `lobbyId`.

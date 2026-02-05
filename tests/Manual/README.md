# Manual Testing Guide

Ten folder zawiera narzędzia do ręcznego testowania funkcjonalności aplikacji.

## Opcje testowania

### 1. Testy API przez skrypt PHP

Uruchom serwer Laravel:
```bash
php artisan serve
```

Następnie uruchom skrypt testowy:
```bash
php tests/Manual/ApiTestScript.php
```

Możesz edytować `ApiTestScript.php`, aby testować różne scenariusze.

### 2. Testy przez Laravel Tinker

Tinker pozwala na interaktywne testowanie aplikacji:

```bash
php artisan tinker
```

Przykłady w Tinker:
```php
// Utwórz użytkownika
$user = App\Models\User::create(['email' => 'test@test.com', 'password' => bcrypt('password')]);

// Utwórz turniej
$tournament = App\Models\Tournament::create([...]);

// Testuj serwis
$gameService = app(App\Services\GameService::class);
$games = $gameService->getActiveGames($tournament->id);
```

### 3. Testy przez curl (PowerShell)

```powershell
# Rejestracja
$body = @{
    email = "test@test.com"
    password = "password123"
    name = "Test User"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/register" -Method Post -Body $body -ContentType "application/json"

# Logowanie
$body = @{
    email = "test@test.com"
    password = "password123"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://localhost:8000/api/login" -Method Post -Body $body -ContentType "application/json"
$token = $response.token

# Pobranie aktywnych gier
$headers = @{
    Authorization = "Bearer $token"
    Accept = "application/json"
}
Invoke-RestMethod -Uri "http://localhost:8000/api/game/active" -Method Get -Headers $headers
```

### 4. Testy przez przeglądarkę (MCP Browser Tools)

Mogę automatycznie testować aplikację webową przez przeglądarkę. Powiedz mi, którą funkcjonalność chcesz przetestować.

### 5. Testy integracyjne z rzeczywistym serwerem

Mogę stworzyć testy PHPUnit, które łączą się z uruchomionym serwerem Laravel zamiast używać testowej bazy danych.

## Którą opcję wybierasz?

- **Szybkie testy API**: Użyj `ApiTestScript.php` lub curl
- **Interaktywne testowanie**: Użyj Tinker
- **Automatyczne testy przez przeglądarkę**: Powiedz mi, co chcesz przetestować
- **Testy integracyjne**: Mogę stworzyć nowe testy PHPUnit

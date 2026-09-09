<?php

namespace App\Support\Http;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Jedna mapa DomainException → HTTP. 422 domyślnie; 409 gdy getCode() === CONFLICT.
 */
final class DomainExceptionHttp
{
    public const CONFLICT = 409;

    public const UNPROCESSABLE = 422;

    public static function status(DomainException $e): int
    {
        return $e->getCode() === self::CONFLICT
            ? self::CONFLICT
            : self::UNPROCESSABLE;
    }

    public static function render(DomainException $e, Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
            return response()->json(['message' => $e->getMessage()], self::status($e));
        }

        return back()->withInput()->with('error', $e->getMessage());
    }
}

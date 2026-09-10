<?php

namespace App\Http\Controllers;

use App\Services\Auth\AccountAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    public function __construct(
        private AccountAuthService $accountAuthService,
    ) {}

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        if (! URL::hasValidSignature($request)) {
            abort(403);
        }

        $outcome = $this->accountAuthService->confirmEmailVerification($id, $hash);

        $message = $outcome === 'already'
            ? 'Ten adres email jest już potwierdzony. Możesz się zalogować.'
            : 'Adres email potwierdzony. Możesz się zalogować.';

        return redirect()
            ->route('pages.loginPanel')
            ->with('success', $message);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $this->accountAuthService->resendVerificationEmail($validated['email']);

        return redirect()
            ->route('verification.notice')
            ->with('registered_email', $validated['email'])
            ->with('success', 'Jeśli konto istnieje i nie jest potwierdzone, wysłaliśmy link aktywacyjny.');
    }
}

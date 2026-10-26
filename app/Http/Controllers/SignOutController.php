<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Signing out from the website (the footer's button): the same as the admin's sign out, but back
 * to the site's home page in the visitor's language rather than to the admin's sign-in page.
 */
final class SignOutController
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($request->string('language')->toString() === 'fr' ? '/fr' : '/');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use LynxCms\Contracts\Actions\Actor;
use LynxCms\Core\Languages\LanguageContext;
use LynxCms\Core\Permissions\Authorizer;

/**
 * The staff's pages: booking a room (/book, /fr/reserver) and their own bookings (/my-bookings,
 * /fr/mes-reservations). Signed in only (the routes' auth middleware sends others to sign in and
 * back), and only for people who may book rooms.
 */
final class StaffPagesController
{
    public function book(string $language): View
    {
        $this->enter($language);

        return view('staff.book', ['language' => $language]);
    }

    public function mine(string $language): View
    {
        $this->enter($language);

        return view('staff.mine', ['language' => $language]);
    }

    /** Lets in people who may book rooms, and speaks the page's language. */
    private function enter(string $language): void
    {
        $id = auth()->id();
        abort_unless($id !== null && app(Authorizer::class)->allows(Actor::user($id), 'rooms.book'), 403);
        app(LanguageContext::class)->set($language);
    }
}

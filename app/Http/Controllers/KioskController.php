<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Bookings\CheckIn;
use App\Actions\Bookings\EndEarly;
use App\Models\KioskLink;
use App\Screens\ScreenState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use LynxCms\Contracts\Actions\Actor;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;

/**
 * A screen's kiosk link (/screens/{token}): the only way a screen reads bookings without signing
 * in. It runs without the session (no cookie) and is never stored, by the page cache or a
 * browser. The page polls its state; a door screen's buttons check a meeting in or end it early,
 * as the system, on its own room only.
 */
final class KioskController
{
    private const array NO_STORE = ['Cache-Control' => 'no-store, private', 'X-Robots-Tag' => 'noindex, nofollow'];

    public function show(string $token): Response
    {
        $screen = $this->screen($token);

        if (! $screen instanceof Page) {
            return $this->invalid();
        }

        $language = ScreenState::language($screen);
        $settings = lynx()->settings();

        return response()->view('kiosk.screen', [
            'token' => $token,
            'state' => ScreenState::of($screen),
            'language' => $language,
            'logo' => $settings?->logo?->url(),
            'accent' => (string) ($settings?->accent_color ?: ''),
            'poll' => Value::int(config('rooms.screen_poll_seconds'), 20),
            'reloadHour' => Value::int(config('rooms.screen_reload_hour'), 3),
        ])->withHeaders(self::NO_STORE);
    }

    public function state(string $token): JsonResponse|Response
    {
        $screen = $this->screen($token);

        return $screen instanceof Page ? response()->json(ScreenState::of($screen), 200, self::NO_STORE) : $this->invalid();
    }

    public function checkIn(Request $request, string $token): JsonResponse|Response
    {
        return $this->act($request, $token, CheckIn::class);
    }

    public function endEarly(Request $request, string $token): JsonResponse|Response
    {
        return $this->act($request, $token, EndEarly::class);
    }

    /** @param  class-string  $action */
    private function act(Request $request, string $token, string $action): JsonResponse|Response
    {
        $screen = $this->screen($token);

        if (! $screen instanceof Page) {
            return $this->invalid();
        }

        try {
            lynx()->actingAs(Actor::system(), fn () => lynx()->run($action, ['booking' => $request->integer('booking'), 'screen' => (int) $screen->id]));
        } catch (ValidationException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'state' => ScreenState::of($screen)], 422, self::NO_STORE);
        }

        return response()->json(ScreenState::of($screen), 200, self::NO_STORE);
    }

    /** The screen a token opens, noting when it was last seen. */
    private function screen(string $token): ?Page
    {
        $link = KioskLink::findByToken($token);
        $screen = $link?->page();

        if ($link === null || $screen === null) {
            return null;
        }

        $link->forceFill(['last_seen_at' => now()])->save();

        return $screen;
    }

    /** A plain answer: the site's 404 page needs a session, which kiosk links never start. */
    private function invalid(): Response
    {
        return response('This screen link is not valid. Ask for a new one.', 404)->withHeaders(self::NO_STORE);
    }
}

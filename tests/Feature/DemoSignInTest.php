<?php

declare(strict_types=1);

use App\Models\User;

/*
 * The public demo's sign-in page offers one button per account, so nobody has to copy a password.
 * Anywhere else those accounts need their passwords, like any other.
 */

beforeEach(fn () => seedDemo());

/** The accounts the sign-in page offers, in order. */
function demoAccounts(): array
{
    preg_match_all('/data-demo-sign-in="([^"]*)"/', (string) test()->get(route('login'))->assertOk()->getContent(), $matches);

    return $matches[1];
}

it('offers the demo accounts on the sign-in page of the demo', function (): void {
    config(['rooms.demo_reset' => true]);

    expect(demoAccounts())->toBe(['admin', 'editor', 'staff']);
});

it('offers no accounts on other sites', function (): void {
    config(['rooms.demo_reset' => false]);

    expect(demoAccounts())->toBe([]);
});

it('signs in with one click, and lands where the account works', function (string $account, string $email, string $home): void {
    config(['rooms.demo_reset' => true]);

    $this->post(route('demo.sign-in', $account))->assertRedirect($home);

    $this->assertAuthenticatedAs(User::query()->where('email', $email)->sole());
})->with([
    'the admin' => ['admin', 'owner@meeting-rooms.example', '/lynx/pages'],
    'the editor' => ['editor', 'mia@meeting-rooms.example', '/lynx/bookings'],
    'staff' => ['staff', 'ana@meeting-rooms.example', '/book'],
]);

it('signs nobody in on other sites', function (): void {
    config(['rooms.demo_reset' => false]);

    $this->post(route('demo.sign-in', 'admin'))->assertNotFound();

    $this->assertGuest();
});

it('knows only the demo accounts', function (): void {
    config(['rooms.demo_reset' => true]);

    $this->post(route('demo.sign-in', 'somebody'))->assertNotFound();

    $this->assertGuest();
});

it('goes back to the sign-in page when a visitor deleted the account', function (): void {
    config(['rooms.demo_reset' => true]);
    User::query()->where('email', 'ana@meeting-rooms.example')->sole()->delete();

    $this->post(route('demo.sign-in', 'staff'))->assertRedirect(route('login'));

    $this->assertGuest();
});

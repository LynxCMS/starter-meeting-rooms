<?php

declare(strict_types=1);

use App\Livewire\BookARoom;
use App\Models\User;
use Livewire\Livewire;

/* The staff's pages: Lynx pages behind a gate, whose Livewire components book through the actions. */

beforeEach(function (): void {
    $this->withoutVite();
    seedDemo();
});

it('links a booking\'s confirmation to the My bookings page, in the visitor\'s language', function (string $language): void {
    $bookingPage = lynx()->pages->find('template=book, include=hidden')->first();
    $myBookings = lynx()->pages->find('template=my-bookings, include=hidden')->first();
    $ana = User::query()->where('email', 'ana@meeting-rooms.example')->firstOrFail();

    lynx()->language($language, function () use ($bookingPage, $myBookings, $ana): void {
        Livewire::actingAs($ana)
            ->test(BookARoom::class, ['pageId' => $bookingPage?->id])
            ->set('booked', ['room' => 'The Lodge', 'date' => '2026-10-27', 'from' => '10:00', 'to' => '11:00'])
            ->assertSeeHtml('href="'.$myBookings?->url().'"');
    });
})->with(['en', 'fr']);

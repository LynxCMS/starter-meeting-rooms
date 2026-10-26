<?php

use App\Http\Controllers\SignOutController;
use App\Http\Controllers\StaffPagesController;
use Illuminate\Support\Facades\Route;

// Pages are Lynx's: its routes answer every address of the site's page tree. These come first.

// The staff's booking pages, in both languages; signing in brings people back here.
Route::middleware('auth')->group(function (): void {
    Route::get('book', [StaffPagesController::class, 'book'])->defaults('language', 'en')->name('staff.book');
    Route::get('fr/reserver', [StaffPagesController::class, 'book'])->defaults('language', 'fr')->name('staff.book.fr');
    Route::get('my-bookings', [StaffPagesController::class, 'mine'])->defaults('language', 'en')->name('staff.mine');
    Route::get('fr/mes-reservations', [StaffPagesController::class, 'mine'])->defaults('language', 'fr')->name('staff.mine.fr');
    Route::post('sign-out', SignOutController::class)->name('staff.sign-out');
});

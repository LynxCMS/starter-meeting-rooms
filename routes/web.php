<?php

use App\Http\Controllers\SignOutController;
use Illuminate\Support\Facades\Route;

// Pages are Lynx's: its routes answer every address of the site's page tree. These come first.

// The staff's booking pages are Lynx pages (templates "book" and "my-bookings"); signing out is ours.
Route::middleware('auth')->post('sign-out', SignOutController::class)->name('staff.sign-out');

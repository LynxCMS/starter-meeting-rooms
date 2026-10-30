<?php

use App\Http\Controllers\DemoScreensController;
use App\Http\Controllers\DemoSignInController;
use App\Http\Controllers\SignOutController;
use Illuminate\Support\Facades\Route;

// Pages are Lynx's: its routes answer every address of the site's page tree. These come first.

// The staff's booking pages are Lynx pages (templates "book" and "my-bookings"); signing out is ours.
Route::middleware('auth')->post('sign-out', SignOutController::class)->name('staff.sign-out');
Route::post('/demo/sign-in/{account}', DemoSignInController::class)->middleware('throttle:20,1')->name('demo.sign-in');
Route::get('/demo/screens', DemoScreensController::class)->middleware('throttle:60,1')->name('demo.screens');

<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\FleetController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FleetController::class, 'index'])->name('home');

Route::get('/fleet', function () {
    return redirect()->route('home', request()->query(), 302);
})->name('fleet.index');

Route::get('/fleet/{vehicle:slug}', [FleetController::class, 'show'])->name('fleet.show');

Route::middleware('auth')->group(function () {
    Route::get('/fleet/{vehicle:slug}/book', [BookingController::class, 'create'])->name('fleet.book');
    Route::post('/fleet/{vehicle:slug}/book', [BookingController::class, 'store'])->name('fleet.book.store');
    Route::post('/fleet/{vehicle:slug}/price-preview', [BookingController::class, 'pricePreview'])->name('fleet.price-preview');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
});

require __DIR__.'/settings.php';

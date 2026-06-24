<?php

use App\Http\Controllers\FleetController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FleetController::class, 'index'])->name('home');

Route::get('/fleet', function () {
    return redirect()->route('home', request()->query(), 302);
})->name('fleet.index');

Route::get('/fleet/{vehicle:slug}', [FleetController::class, 'show'])->name('fleet.show');
Route::get('/fleet/{vehicle:slug}/book', [FleetController::class, 'book'])->name('fleet.book');

require __DIR__.'/settings.php';

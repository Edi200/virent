<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => 'ViRent — coming soon')->name('home');

Route::get('/dashboard', fn () => 'Customer dashboard — coming soon')->middleware(['auth'])->name('dashboard');

require __DIR__.'/settings.php';

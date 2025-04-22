<?php

declare(strict_types=1);

// Route::view('/', 'welcome');
//
// Route::view('dashboard', 'dashboard')
//    ->middleware(['auth', 'verified'])
//    ->name('dashboard');
//
// Route::view('profile', 'profile')
//    ->middleware(['auth'])
//    ->name('profile');
//
// require __DIR__.'/auth.php';
//

// This is in web.php for now because it's simpler.
// `artisan install:api` will install Sanctum, which we don't need yet. And simply adding api.php doesn't
// seem to work.
Route::webhooks('api/webhook/repository');

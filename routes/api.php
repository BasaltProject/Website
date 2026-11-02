<?php

use App\Http\Controllers\TimingsController;
use Illuminate\Support\Facades\Route;

// Where a running Basalt Server sends the profile `/timings` just recorded.
Route::match(['put', 'post'], '/timings', [TimingsController::class, 'store'])
    ->middleware('throttle:timings')
    ->name('api.timings.store');

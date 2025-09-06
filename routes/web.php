<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// SPA fallback route - all routes handled by Vue Router
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

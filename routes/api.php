<?php

declare(strict_types=1);

use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Ticket routes
Route::apiResource('tickets', TicketController::class);
Route::post('tickets/{ticket}/classify', [TicketController::class, 'classify']);
Route::get('tickets-export', [TicketController::class, 'export']);

// Stats routes
Route::get('stats', [StatsController::class, 'index']);

<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\TranslationExportController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('translations/export', TranslationExportController::class)
        ->middleware('abilities:translations:read');

    Route::get('translations/search', [TranslationController::class, 'index'])
        ->middleware('abilities:translations:read');

    Route::apiResource('translations', TranslationController::class)
        ->only(['index', 'store', 'show', 'update'])
        ->middlewareFor(['index', 'show'], 'abilities:translations:read')
        ->middlewareFor(['store', 'update'], 'abilities:translations:write');
});
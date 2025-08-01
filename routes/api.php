<?php

use App\Http\Controllers\Api\AiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/ai/{prompt}', [AiController::class, 'index'])->name('api.ai');
    Route::post('/ai/generate-content/{prompt}', [AiController::class, 'generateContent'])->name('api.ai.generate-content');
});

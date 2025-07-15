<?php

use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\PromptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('prompt/{prompt}', [PromptController::class, 'getPrompt'])->name('api.prompt');

    Route::post('/generate-with-ai/{prompt}', [AiController::class, 'index'])->name('api.generate-with-ai');
});

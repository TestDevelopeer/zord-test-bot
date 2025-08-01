<?php

use App\Http\Controllers\API\V1\TelegramBotController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('telegram')->group(function () {
        Route::post('/webhook', [TelegramBotController::class, 'webhook']);
        Route::get('/set/webhook', [TelegramBotController::class, 'setWebhook'])->name('telegram.set.webhook');
    });
});

<?php

use App\Http\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('platform.main');
});

Route::post('/telegram/webhook', [TelegramBotController::class, 'webhook']);

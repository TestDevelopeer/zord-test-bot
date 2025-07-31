<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotController extends Controller
{
    public function webhook(): JsonResponse
    {
        try {
            Telegram::commandsHandler(true);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error("TelegramBot set webhook error: {$e->getMessage()}");

            return response()->json(['status' => 'error']);
        }
    }
}

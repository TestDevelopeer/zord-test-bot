<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotService
{
    public function __construct()
    {
    }

    public function webhook(): JsonResponse
    {
        Telegram::commandsHandler(true);

        return response()->json(['status' => 'success']);
    }

    /**
     * @throws TelegramSDKException
     */
    public function setWebhook(): JsonResponse
    {
        $response = Telegram::setWebhook(['url' => config('telegram.bots.mybot.webhook_url')]);

        return response()->json($response);
    }
}

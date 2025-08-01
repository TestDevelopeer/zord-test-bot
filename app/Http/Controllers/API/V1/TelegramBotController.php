<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotController extends Controller
{
    public function __construct(private readonly TelegramBotService $telegramBotService)
    {

    }
	public function webhook(): JsonResponse
	{
		try {
            return $this->telegramBotService->webhook();
		} catch (\Exception $e) {
			Log::error("TelegramBot handle webhook error: {$e->getMessage()}");

			return response()->json(['status' => 'error'], 500);
		}
	}

    public function setWebhook(): JsonResponse
    {
        try {
            return $this->telegramBotService->setWebhook();
        } catch (\Exception $e) {
            Log::error("TelegramBot set webhook error: {$e->getMessage()}");

            return response()->json(['status' => 'error'], 500);
        }

    }
}

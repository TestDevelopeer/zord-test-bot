<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\TelegramBotService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotController extends Controller
{
    public function __construct(
        private readonly TelegramBotService $telegramBotService,
    ) {
    }

    /**
     * Обработка webhook от Telegram
     */
    public function webhook(): JsonResponse
    {
        try {
            $update = Telegram::commandsHandler(true);

            if (!$this->telegramBotService->isValidUpdate($update)) {
                return $this->errorResponse('Invalid update data');
            }

            $this->telegramBotService->processUpdate($update);

            return response()->json(['status' => 'success']);
        } catch (Exception $e) {
            Log::error("TelegramBot handle webhook error: {$e->getMessage()}", [
                'exception' => $e,
                'update' => $update ?? null
            ]);

            return $this->errorResponse('Internal server error', 500);
        }
    }



    /**
     * Возврат ошибки в формате JSON
     */
    private function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $code);
    }

    /**
     * Установка webhook для Telegram бота
     */
    public function setWebhook(): JsonResponse
    {
        try {
            $response = $this->telegramBotService->setWebhook();

            return response()->json([
                'status' => 'success',
                'data' => $response
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (Exception $e) {
            Log::error("TelegramBot set webhook error: {$e->getMessage()}", [
                'exception' => $e,
                'webhook_url' => config('telegram.bots.mybot.webhook_url')
            ]);

            return $this->errorResponse('Ошибка при установке webhook', 500);
        }
    }
}

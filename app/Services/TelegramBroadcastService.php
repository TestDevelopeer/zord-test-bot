<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramBroadcastService
{
    private TelegramBotService $telegramBotService;
    
    public function __construct(TelegramBotService $telegramBotService)
    {
        $this->telegramBotService = $telegramBotService;
    }

    /**
     * Отправляет массовое сообщение всем активным пользователям
     */
    public function broadcastMessage(string $message, ?string $parseMode = null): array
    {
        $stats = [
            'total' => 0,
            'sent' => 0,
            'failed' => 0,
            'kicked' => 0,
            'errors' => []
        ];

        // Получаем пользователей пакетами для оптимизации памяти
        $users = User::whereNotNull('telegram_chat_id')
            ->where('telegram_status', '!=', 'kicked')
            ->get(['id', 'telegram_chat_id', 'telegram_status', 'name']);

        $stats['total'] = $users->count();

        if ($stats['total'] === 0) {
            Log::info('Нет пользователей для массовой рассылки');
            return $stats;
        }

        Log::info("Начинаем массовую рассылку для {$stats['total']} пользователей");

        // Обрабатываем пользователей пакетами по 50 для оптимизации
        $users->chunk(50)->each(function (Collection $chunk) use ($message, $parseMode, &$stats) {
            $this->sendToChunk($chunk, $message, $stats, $parseMode);
            
            // Пауза между пакетами для соблюдения лимитов API (30 сообщений в секунду)
            if ($chunk->count() === 50) {
                sleep(2);
            }
        });

        Log::info('Массовая рассылка завершена', $stats);
        
        return $stats;
    }

    /**
     * Отправляет сообщения пакету пользователей
     */
    private function sendToChunk(Collection $chunk, string $message, array &$stats, ?string $parseMode = null): void
    {
        foreach ($chunk as $user) {
            try {
                $this->telegramBotService->sendMessage($user->telegram_chat_id, $message, $parseMode);
                $stats['sent']++;
                
                Log::debug("Сообщение отправлено пользователю {$user->id} (chat_id: {$user->telegram_chat_id})");
                
                // Небольшая пауза между сообщениями (максимум 30 сообщений в секунду)
                usleep(35000); // 35ms пауза
                
            } catch (TelegramSDKException $e) {
                $this->handleTelegramError($user, $e, $stats);
            } catch (\Exception $e) {
                $this->handleGeneralError($user, $e, $stats);
            }
        }
    }

    /**
     * Обработка ошибок Telegram API
     */
    private function handleTelegramError(User $user, TelegramSDKException $e, array &$stats): void
    {
        $errorMessage = $e->getMessage();
        $stats['failed']++;
        
        // Проверяем типы ошибок, которые означают что бот заблокирован
        if ($this->isBotBlockedError($errorMessage)) {
            $this->markUserAsKicked($user);
            $stats['kicked']++;
            
            Log::info("Пользователь {$user->id} заблокировал бота, статус изменен на kicked", [
                'telegram_chat_id' => $user->telegram_chat_id,
                'error' => $errorMessage
            ]);
        } else {
            // Другие ошибки Telegram API
            $stats['errors'][] = "Пользователь {$user->id}: {$errorMessage}";
            
            Log::warning("Ошибка отправки сообщения пользователю {$user->id}", [
                'telegram_chat_id' => $user->telegram_chat_id,
                'error' => $errorMessage
            ]);
        }
    }

    /**
     * Обработка общих ошибок
     */
    private function handleGeneralError(User $user, \Exception $e, array &$stats): void
    {
        $stats['failed']++;
        $stats['errors'][] = "Пользователь {$user->id}: {$e->getMessage()}";
        
        Log::error("Общая ошибка при отправке сообщения пользователю {$user->id}", [
            'telegram_chat_id' => $user->telegram_chat_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Проверяет, является ли ошибка признаком блокировки бота
     */
    private function isBotBlockedError(string $errorMessage): bool
    {
        $blockedErrorPatterns = [
            'bot was blocked by the user',
            'user is deactivated',
            'chat not found',
            'bot can\'t initiate conversation',
            'forbidden'
        ];

        $errorMessage = strtolower($errorMessage);
        
        foreach ($blockedErrorPatterns as $pattern) {
            if (str_contains($errorMessage, $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Помечает пользователя как заблокировавшего бота
     */
    private function markUserAsKicked(User $user): void
    {
        try {
            $user->update(['telegram_status' => 'kicked']);
        } catch (\Exception $e) {
            Log::error("Ошибка при обновлении статуса пользователя {$user->id}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Получает статистику пользователей для отображения перед рассылкой
     */
    public function getBroadcastStats(): array
    {
        $total = User::whereNotNull('telegram_chat_id')->count();
        $active = User::whereNotNull('telegram_chat_id')
            ->where('telegram_status', '!=', 'kicked')
            ->count();
        $kicked = User::where('telegram_status', 'kicked')->count();

        return [
            'total_with_telegram' => $total,
            'active_users' => $active,
            'kicked_users' => $kicked,
        ];
    }
}
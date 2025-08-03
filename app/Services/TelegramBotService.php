<?php

namespace App\Services;

use App\Contracts\MessageSenderInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class TelegramBotService implements MessageSenderInterface
{
    private MessageHandlerService $messageHandlerService;

    public function __construct(
        private readonly UserService $userService
    ) {
    }

    /**
     * Устанавливает MessageHandlerService
     */
    public function setMessageHandlerService(MessageHandlerService $messageHandlerService): void
    {
        $this->messageHandlerService = $messageHandlerService;
    }

    /**
     * Отправляет простое сообщение
     * @throws TelegramSDKException
     */
    public function sendMessage(int $chatId, string $text): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }

    /**
     * Отправляет сообщение с inline кнопкой
     * @throws TelegramSDKException
     */
    public function sendMessageWithInlineButton(int $chatId, string $text, ?int $userId = null): void
    {
        if($userId) {
            $text = "Ваш ID: $userId \nВаше сообщение: $text";
        }

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $this->makeInlineButton()
        ]);
    }

    public function makeInlineButton(): Keyboard
    {
        return Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton(config('telegram.buttons.inlineIncClickButton')),
            ]);
    }

    /**
     * Валидация входящих данных обновления
     */
    public function isValidUpdate(array|Update $update): bool
    {
        return !empty($update) && (
            isset($update['callback_query']) ||
            isset($update['message'])
        );
    }

    /**
     * Обработка обновления в зависимости от типа
     * @throws TelegramSDKException
     */
    public function processUpdate(array|Update $update): void
    {
        try {
            // Обработка callback_query (нажатие на Inline кнопку)
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
                return;
            }

            // Обработка сообщений
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }
        } catch (Exception $e) {
            Log::error("TelegramBotService process update error: {$e->getMessage()}", [
                'exception' => $e,
                'update' => $update
            ]);
            throw $e;
        }
    }

    /**
     * Обработка сообщений в зависимости от типа
     * @throws TelegramSDKException
     */
    private function handleMessage(array $message): void
    {
        if (isset($this->messageHandlerService)) {
            $this->messageHandlerService->handle($message);
        } else {
            // Fallback для случая, когда MessageHandlerService не установлен
            Log::warning('MessageHandlerService не установлен, используется fallback обработка');
            if (isset($message['chat']['id'])) {
                $this->sendMessage($message['chat']['id'], 'Сообщение получено');
            }
        }
    }



    /**
     * Обработка callback query
     * @throws TelegramSDKException
     */
    public function handleCallbackQuery(array $callback): void
    {
        try {
            $chatId = $callback['message']['chat']['id'];
            $data = $callback['data'];

            // Обработка действий
            if ($data === config('telegram.buttons.inlineIncClickButton.callback_data')) {
                try {
                    $cntClick = $this->userService->incrementTelegramClick($chatId);
                    $message = config('telegram.messages.click_count_template', 'Количество нажатий за всё время: {count}');
                    $message = str_replace('{count}', $cntClick, $message);
                    $this->sendMessageWithInlineButton($chatId, $message);
                } catch (InvalidArgumentException $e) {
                    Log::warning("User not found for telegram click increment: {$e->getMessage()}", [
                        'chat_id' => $chatId,
                        'callback_data' => $data
                    ]);
                    $this->sendMessage($chatId, config('telegram.messages.user_not_found', 'Пользователь не найден. Выполните команду /start'));
                }
            }

            Telegram::answerCallbackQuery([
                'callback_query_id' => $callback['id']
            ]);
        } catch (Exception $e) {
            Log::error("TelegramBotService callback query error: {$e->getMessage()}", [
                'exception' => $e,
                'callback' => $callback
            ]);
            throw $e;
        }
    }

    /**
     * Установка webhook для Telegram бота
     * @throws TelegramSDKException
     */
    public function setWebhook(): bool
    {
        $webhookUrl = config('telegram.bots.mybot.webhook_url');

        if (empty($webhookUrl)) {
            throw new InvalidArgumentException('Webhook URL не настроен в конфигурации');
        }

        $response = Telegram::setWebhook(['url' => $webhookUrl]);

        Log::info('Telegram webhook успешно установлен', ['url' => $webhookUrl]);

        return $response;
    }
}

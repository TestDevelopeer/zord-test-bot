<?php

namespace App\Telegram\MessageHandlers;

use Telegram\Bot\Exceptions\TelegramSDKException;

interface MessageHandlerInterface
{
    /**
     * Проверяет, может ли обработчик обработать данное сообщение
     */
    public function canHandle(array $message): bool;

    /**
     * Обрабатывает сообщение
     * @throws TelegramSDKException
     */
    public function handle(array $message): void;
}

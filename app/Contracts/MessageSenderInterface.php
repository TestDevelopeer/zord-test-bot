<?php

namespace App\Contracts;

use Telegram\Bot\Exceptions\TelegramSDKException;

interface MessageSenderInterface
{
    /**
     * Отправляет простое сообщение
     * @throws TelegramSDKException
     */
    public function sendMessage(int $chatId, string $text): void;

    /**
     * Отправляет сообщение с inline кнопкой
     * @throws TelegramSDKException
     */
    public function sendMessageWithInlineButton(int $chatId, string $text, ?int $userId = null): void;
}
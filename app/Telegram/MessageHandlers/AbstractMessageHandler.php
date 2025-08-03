<?php

namespace App\Telegram\MessageHandlers;

use App\Contracts\MessageSenderInterface;

abstract class AbstractMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        protected readonly MessageSenderInterface $messageSender
    ) {
    }

    /**
     * Получает ID чата из сообщения
     */
    protected function getChatId(array $message): int
    {
        return $message['chat']['id'];
    }

    /**
     * Получает ID пользователя из сообщения (если есть)
     */
    protected function getUserId(array $message): ?int
    {
        return $message['from']['id'] ?? null;
    }
}
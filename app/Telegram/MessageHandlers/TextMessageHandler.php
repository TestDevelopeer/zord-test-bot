<?php

namespace App\Telegram\MessageHandlers;

class TextMessageHandler extends AbstractMessageHandler
{
    public function canHandle(array $message): bool
    {
        return isset($message['text']) && !isset($message['entities']);
    }

    public function handle(array $message): void
    {
        $chatId = $this->getChatId($message);
        $text = $message['text'];
        $userId = $this->getUserId($message);

        $this->messageSender->sendMessageWithInlineButton($chatId, $text, $userId);
    }
}
<?php

namespace App\Services;

use App\Telegram\MessageHandlers\DocumentMessageHandler;
use App\Telegram\MessageHandlers\MessageHandlerInterface;
use App\Telegram\MessageHandlers\PhotoMessageHandler;
use App\Telegram\MessageHandlers\TextMessageHandler;
use Telegram\Bot\Exceptions\TelegramSDKException;

class MessageHandlerService
{
    /**
     * @var MessageHandlerInterface[]
     */
    private array $handlers;

    public function __construct(
        TextMessageHandler $textHandler,
        DocumentMessageHandler $documentHandler,
        PhotoMessageHandler $photoHandler
    ) {
        $this->handlers = [
            $textHandler,
            $documentHandler,
            $photoHandler,
        ];
    }

    /**
     * Обрабатывает сообщение, используя подходящий обработчик
     * @throws TelegramSDKException
     */
    public function handle(array $message): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($message)) {
                $handler->handle($message);
                return;
            }
        }
    }
}

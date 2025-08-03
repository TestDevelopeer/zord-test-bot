<?php

namespace App\Telegram\MessageHandlers;

use Illuminate\Support\Number;

class DocumentMessageHandler extends AbstractMessageHandler
{
    public function canHandle(array $message): bool
    {
        return isset($message['document']);
    }

    public function handle(array $message): void
    {
        $chatId = $this->getChatId($message);
        $document = $message['document'];

        $fileName = $document['file_name'] ?? config('telegram.messages.unknown_file', 'Неизвестный файл');
        $fileSize = isset($document['file_size'])
            ? Number::fileSize($document['file_size'])
            : config('telegram.messages.unknown_size', 'Неизвестный размер');

        $text = $this->formatFileMessage($fileName, $fileSize);
        $this->messageSender->sendMessageWithInlineButton($chatId, $text);
    }

    /**
     * Форматирование сообщения о файле
     */
    private function formatFileMessage(string $fileName, string $fileSize): string
    {
        return str_replace(['{fileName}', '{fileSize}'], [$fileName, $fileSize], config('telegram.messages.file_info_template', "Имя файла: {fileName}\nРазмер файла: {fileSize}"));
    }
}

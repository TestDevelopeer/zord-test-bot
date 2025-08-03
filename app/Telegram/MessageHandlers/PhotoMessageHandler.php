<?php

namespace App\Telegram\MessageHandlers;

use Illuminate\Support\Number;

class PhotoMessageHandler extends AbstractMessageHandler
{
    public function canHandle(array $message): bool
    {
        return isset($message['photo']);
    }

    public function handle(array $message): void
    {
        $chatId = $this->getChatId($message);
        $photos = $message['photo'];

        $fileName = $message['caption'] ?? config('telegram.messages.default_photo_caption', 'добавьте подпись при отправке фото');

        // Получаем самое большое фото (последний элемент массива)
        $largestPhoto = end($photos);
        $fileSize = isset($largestPhoto['file_size'])
            ? Number::fileSize($largestPhoto['file_size'])
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

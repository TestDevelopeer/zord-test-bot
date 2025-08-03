<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Telegram;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class BroadcastLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Quill::make('message')
                ->title('Сообщение для рассылки')
                ->help('Составьте текст сообщения с форматированием. Поддерживается: жирный текст, курсив, подчеркивание, зачеркивание, ссылки и код.')
                ->placeholder('Введите текст сообщения...')
                ->required(),
        ];
    }
}
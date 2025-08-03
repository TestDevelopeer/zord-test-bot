<?php

namespace App\Services;

use App\Models\User;
use InvalidArgumentException;

class UserService
{
    public function __construct()
    {
    }

    public function incrementTelegramClick($chatId)
    {
        $user = User::where('telegram_chat_id', '=', $chatId)->first();

        if (!$user) {
            throw new InvalidArgumentException("Пользователь с telegram_chat_id $chatId не найден");
        }

        ++$user->telegram_clicks;
        $user->save();

        return $user->telegram_clicks;
    }
}

<?php

namespace App\Telegram\Bot\Commands;

use App\Models\User;
use App\Services\TelegramBotService;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Exceptions\TelegramSDKException;

class StartCommand extends Command
{
	protected string $name = 'start';

    protected string $description = 'Start bot and register user';

    public function __construct(private readonly TelegramBotService $telegramBotService)
    {

    }

    /**
     * {@inheritdoc}
     * @throws TelegramSDKException
     */
	public function handle(): void
	{
		$chatId = $this->getUpdate()->getMessage()->chat->id;
		$userName = $this->getUpdate()->getMessage()->from->username;
        $userId = $this->getUpdate()->getMessage()->from->id;
        $text = $this->getUpdate()->getMessage()->text;

		User::updateOrInsert(
			['telegram_chat_id' => $chatId],
			[
                'name' => $userName,
                'telegram_status' => 'connected',
                'email' => 'user@user.com'
            ]
		);

        $this->telegramBotService->sendMessageWithInlineButton($chatId, $text, $userId);
	}
}

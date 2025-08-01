<?php

namespace App\Telegram\Bot\Commands;

use App\Models\User;
use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
	protected string $name = 'start';

	/**
	 * {@inheritdoc}
	 */
	public function handle(): void
	{
		$chatId = $this->getUpdate()->getMessage()->chat->id;
		$userName = $this->getUpdate()->getMessage()->from->username;

		User::updateOrInsert(
			['telegram_chat_id' => $chatId],
			['name' => $userName]
		);

		$this->replyWithMessage([
			'text' => "Вы успешно зарегистрированы!"
		]);
	}
}

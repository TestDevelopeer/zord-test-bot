<?php

namespace App\Providers;

use App\Contracts\MessageSenderInterface;
use App\Services\MessageHandlerService;
use App\Services\TelegramBotService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Регистрируем TelegramBotService как singleton
        $this->app->singleton(TelegramBotService::class);

        // Привязываем интерфейс к тому же экземпляру
        $this->app->bind(MessageSenderInterface::class, function ($app) {
            return $app->make(TelegramBotService::class);
        });

        // Регистрируем MessageHandlerService отдельно
        $this->app->singleton(MessageHandlerService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Инициализируем связи после загрузки приложения
        $this->app->booted(function ($app) {
            try {
                $telegramBotService = $app->make(TelegramBotService::class);
                $messageHandlerService = $app->make(MessageHandlerService::class);

                // Устанавливаем MessageHandlerService в TelegramBotService
                $telegramBotService->setMessageHandlerService($messageHandlerService);
            } catch (Exception $e) {
                // Если что-то пошло не так, просто логируем
                Log::error('Ошибка при инициализации TelegramBotService: ' . $e->getMessage());
            }
        });
    }
}

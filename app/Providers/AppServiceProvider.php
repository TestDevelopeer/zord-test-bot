<?php

namespace App\Providers;

use App\Contracts\MessageSenderInterface;
use App\Services\MessageHandlerService;
use App\Services\TelegramBotService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
// Добавьте эти импорты
use GuzzleHttp\Client;
use Telegram\Bot\HttpClients\GuzzleHttpClient;

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
        
        // Настройка HTTP клиента для Telegram SDK только в локальной среде
        if ($this->app->environment('local')) {
            $this->app->singleton('telegram.http_client', function () {
                $guzzleClient = new Client([
                    'verify' => false, // Отключаем проверку SSL для разработки
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ]
                ]);
                
                return new GuzzleHttpClient($guzzleClient);
            });
            
            // Переопределяем конфигурацию Telegram для локальной среды
            $this->app->extend('config', function ($config) {
                $telegramConfig = $config->get('telegram');
                $telegramConfig['http_client_handler'] = $this->app->make('telegram.http_client');
                $config->set('telegram', $telegramConfig);
                return $config;
            });
        }
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

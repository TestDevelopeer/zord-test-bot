<?php

namespace Tests\Feature;

use App\Http\Controllers\API\V1\TelegramBotController;
use App\Services\TelegramBotService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Tests\TestCase;

class TelegramBotControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Тест успешной установки webhook через unit тест
     */
    public function test_set_webhook_unit_success(): void
    {
        // Arrange
        $expectedResponse = true;
        $mock = $this->createMock(TelegramBotService::class);
        $mock->expects($this->once())
            ->method('setWebhook')
            ->willReturn($expectedResponse);

        $controller = new TelegramBotController($mock);

        // Act
        $response = $controller->setWebhook();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResponse, $responseData['data']);
    }

    /**
     * Тест обработки InvalidArgumentException через unit тест
     */
    public function test_set_webhook_unit_invalid_argument_exception(): void
    {
        // Arrange
        $errorMessage = 'Webhook URL не настроен в конфигурации';
        $mock = $this->createMock(TelegramBotService::class);
        $mock->expects($this->once())
            ->method('setWebhook')
            ->willThrowException(new InvalidArgumentException($errorMessage));

        $controller = new TelegramBotController($mock);

        // Act
        $response = $controller->setWebhook();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals($errorMessage, $responseData['message']);
    }

    /**
     * Тест обработки общего исключения через unit тест
     */
    public function test_set_webhook_unit_general_exception(): void
    {
        // Arrange
        $exceptionMessage = 'Telegram API error';
        $exception = new Exception($exceptionMessage);
        
        $mock = $this->createMock(TelegramBotService::class);
        $mock->expects($this->once())
            ->method('setWebhook')
            ->willThrowException($exception);

        // Mock логирования
        Log::shouldReceive('error')
            ->once()
            ->with(
                "TelegramBot set webhook error: {$exceptionMessage}",
                [
                    'exception' => $exception,
                    'webhook_url' => config('telegram.bots.mybot.webhook_url')
                ]
            );

        $controller = new TelegramBotController($mock);

        // Act
        $response = $controller->setWebhook();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Ошибка при установке webhook', $responseData['message']);
    }

    /**
     * Интеграционный тест через HTTP endpoint
     */
    public function test_set_webhook_via_http_success(): void
    {
        // Arrange
        $mock = $this->createMock(TelegramBotService::class);
        $mock->expects($this->once())
            ->method('setWebhook')
            ->willReturn(true);
            
        $this->app->instance(TelegramBotService::class, $mock);

        // Act
        $response = $this->get('/api/v1/telegram/set/webhook');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => true
            ]);
    }

    /**
     * Интеграционный тест через HTTP endpoint с ошибкой
     */
    public function test_set_webhook_via_http_error(): void
    {
        // Arrange
        $mock = $this->createMock(TelegramBotService::class);
        $mock->expects($this->once())
            ->method('setWebhook')
            ->willThrowException(new InvalidArgumentException('Webhook URL не настроен'));
            
        $this->app->instance(TelegramBotService::class, $mock);

        // Act
        $response = $this->get('/api/v1/telegram/set/webhook');

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Webhook URL не настроен'
            ]);
    }
}
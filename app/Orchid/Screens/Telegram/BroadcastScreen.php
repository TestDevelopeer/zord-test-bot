<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Telegram;

use App\Orchid\Layouts\Telegram\BroadcastLayout;
use App\Services\TelegramBroadcastService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Illuminate\Support\Facades\Log;

class BroadcastScreen extends Screen
{
    private TelegramBroadcastService $broadcastService;

    public function __construct(TelegramBroadcastService $broadcastService)
    {
        $this->broadcastService = $broadcastService;
    }

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $stats = $this->broadcastService->getBroadcastStats();

        return [
            'stats' => $stats,
            'message' => '',
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Массовая рассылка в Telegram';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Отправка сообщений всем активным пользователям в Telegram. Пользователи со статусом "kicked" будут пропущены.';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Отправить рассылку')
                ->icon('bs.send')
                ->method('sendBroadcast')
                ->confirm('Вы уверены, что хотите отправить сообщение всем активным пользователям?')
                ->canSee(auth()->user()->hasAccess('platform.systems.users')),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            Layout::view('platform.partials.broadcast-stats'),
            BroadcastLayout::class,
        ];
    }

    /**
     * Отправка массовой рассылки
     */
    public function sendBroadcast(Request $request): void
    {
        $request->validate([
            'message' => 'required|string|min:1|max:4000',
        ], [
            'message.required' => 'Сообщение не может быть пустым',
            'message.min' => 'Сообщение должно содержать хотя бы 1 символ',
            'message.max' => 'Сообщение не может превышать 4000 символов',
        ]);

        $rawMessage = $request->input('message');
        $message = $this->convertToTelegramHTML($rawMessage);
        
        if (empty(trim(strip_tags($message)))) {
            Toast::error('Сообщение не может быть пустым');
            return;
        }

        try {
            Log::info('Начата массовая рассылка', [
                'user_id' => auth()->id(),
                'message_length' => strlen($message)
            ]);

            $stats = $this->broadcastService->broadcastMessage($message, 'HTML');

            $successMessage = "Рассылка завершена! Отправлено: {$stats['sent']}, Ошибок: {$stats['failed']}";
            
            if ($stats['kicked'] > 0) {
                $successMessage .= ", Заблокировали бота: {$stats['kicked']}";
            }

            Toast::success($successMessage);

            if (!empty($stats['errors']) && count($stats['errors']) <= 5) {
                foreach ($stats['errors'] as $error) {
                    Toast::warning($error);
                }
            } elseif (!empty($stats['errors'])) {
                Toast::warning("Обнаружено {$stats['failed']} ошибок. Подробности в логах.");
            }

            Log::info('Массовая рассылка завершена', [
                'user_id' => auth()->id(),
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка при массовой рассылке', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Toast::error('Произошла ошибка при отправке рассылки. Проверьте логи.');
        }
    }

    /**
     * Конвертирует HTML из Quill редактора в Telegram-совместимый HTML
     */
    private function convertToTelegramHTML(string $html): string
    {
        // Если это обычный текст без HTML, возвращаем как есть
        if (strip_tags($html) === $html) {
            return $html;
        }
        
        // Удаляем ненужные теги и атрибуты, оставляем только поддерживаемые Telegram
        $html = preg_replace('/<p[^>]*>/i', '', $html);
        $html = str_replace('</p>', "\n", $html);
        
        // Удаляем div и span, сохраняя содержимое
        $html = preg_replace('/<div[^>]*>/i', '', $html);
        $html = str_replace('</div>', "\n", $html);
        $html = preg_replace('/<span[^>]*>/i', '', $html);
        $html = str_replace('</span>', '', $html);
        
        // Конвертируем основные теги форматирования
        $html = preg_replace('/<strong[^>]*>/i', '<b>', $html);
        $html = str_replace('</strong>', '</b>', $html);
        $html = preg_replace('/<em[^>]*>/i', '<i>', $html);
        $html = str_replace('</em>', '</i>', $html);
        $html = preg_replace('/<bold[^>]*>/i', '<b>', $html);
        $html = str_replace('</bold>', '</b>', $html);
        
        // Обрабатываем подчеркивание и зачеркивание
        $html = preg_replace('/<u[^>]*>/i', '<u>', $html);
        $html = preg_replace('/<s[^>]*>/i', '<s>', $html);
        $html = preg_replace('/<del[^>]*>/i', '<s>', $html);
        $html = str_replace('</del>', '</s>', $html);
        $html = preg_replace('/<strike[^>]*>/i', '<s>', $html);
        $html = str_replace('</strike>', '</s>', $html);
        
        // Обрабатываем код
        $html = preg_replace('/<code[^>]*>/i', '<code>', $html);
        
        // Обрабатываем блоки кода (pre)
        $html = preg_replace('/<pre[^>]*>/i', '<pre>', $html);
        
        // Обрабатываем ссылки - оставляем только href
        $html = preg_replace('/<a[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/i', '<a href="$1">$2</a>', $html);
        
        // Обрабатываем заголовки (конвертируем в жирный текст)
        $html = preg_replace('/<h[1-6][^>]*>/i', '<b>', $html);
        $html = preg_replace('/<\/h[1-6]>/i', '</b>', $html);
        
        // Удаляем лишние переносы строк
        $html = preg_replace('/\n+/', "\n", $html);
        $html = trim($html);
        
        // Если после обработки остались неподдерживаемые теги, удаляем их
        $allowedTags = '<b><i><u><s><code><pre><a>';
        $html = strip_tags($html, $allowedTags);
        
        return $html;
    }

    /**
     * Права доступа для экрана
     */
    public function permission(): ?iterable
    {
        return [
            'platform.systems.users',
        ];
    }
}
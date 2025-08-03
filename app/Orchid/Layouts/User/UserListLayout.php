<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use App\Models\User;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Persona;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class UserListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'users';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('name', 'Имя')
                ->sort()
                ->cantHide()
                ->filter(Input::make())
                ->render(fn (User $user) => new Persona($user->presenter())),

            TD::make('email', 'Email')
                ->sort()
                ->cantHide()
                ->filter(Input::make())
                ->render(fn (User $user) => ModalToggle::make($user->email)
                    ->modal('editUserModal')
                    ->modalTitle($user->presenter()->title())
                    ->method('saveUser')
                    ->asyncParameters([
                        'user' => $user->id,
                    ])),

            TD::make('created_at', 'Создан')
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->sort(),

            TD::make('updated_at', 'Последнее изменение')
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->sort(),

            TD::make('telegram_chat_id', 'Telegram Chat ID')
                ->sort()
                ->filter(Input::make())
                ->render(fn (User $user) => $user->telegram_chat_id ?? '-'),

            TD::make('telegram_status', 'Telegram Статус')
                ->sort()
                ->filter(Input::make())
                ->render(fn (User $user) => match($user->telegram_status) {
                    'connected' => '<span class="badge bg-success">Подключен</span>',
                    'kicked' => '<span class="badge bg-danger">Исключен</span>',
                    default => '<span class="badge bg-secondary">' . ($user->telegram_status ?? 'Неизвестно') . '</span>'
                }),

            TD::make('telegram_clicks', 'Telegram Клики')
                ->sort()
                ->align(TD::ALIGN_CENTER)
                ->render(fn (User $user) => '<span class="badge bg-primary">' . ($user->telegram_clicks ?? 0) . '</span>'),

            TD::make('Действия')
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (User $user) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([

                        Link::make('Редактировать')
                            ->route('platform.systems.users.edit', $user->id)
                            ->icon('bs.pencil'),

                        Button::make('Удалить')
                            ->icon('bs.trash3')
                            ->confirm('После удаления аккаунта все его ресурсы и данные будут безвозвратно удалены. Перед удалением аккаунта, пожалуйста, скачайте любые данные или информацию, которую вы хотите сохранить.')
                            ->method('remove', [
                                'id' => $user->id,
                            ]),
                    ])),
        ];
    }
}

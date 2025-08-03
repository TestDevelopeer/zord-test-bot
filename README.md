# Telegram Bot Laravel Project

Проект представляет собой Laravel-приложение с интеграцией Telegram бота, которое можно развернуть как в Docker контейнерах, так и локально без Docker, с использованием Zrok для публичного туннелирования.

## Возможности бота

- **Команда /start**: Регистрирует пользователя в базе данных и отправляет сообщение (ID, Text) с интерактивной кнопкой
- **Обычные сообщения**: Отправляет сообщение (ID, Text) с интерактивной кнопкой
- **Интерактивная кнопка**: Ведет счетчик нажатий и отображает количество кликов
- **Обработка файлов**: При отправке изображений или документов выводит название и размер файла

## Пошаговое развертывание

### 1. Установка Zrok CLI

1. Перейдите на страницу [установки Zrok для Windows](https://docs.zrok.io/docs/guides/install/windows/)
2. Скачайте архив для Windows (`zrok*windows*.tar.gz`)
3. **НЕ РАСПАКОВЫВАЯ АРХИВ**, откройте PowerShell в папке где находится скачанный файл
4. Скопируйте данную команду полностью и выполните в терминале:

```powershell
$binDir = Join-Path -Path $env:USERPROFILE -ChildPath "bin"
New-Item -Path $binDir -ItemType Directory -ErrorAction SilentlyContinue
$latest = Get-ChildItem -Path .\zrok*windows*.tar.gz | Sort-Object LastWriteTime | Select-Object -Last 1
tar -xf $latest.FullName -C $binDir zrok.exe
$currentPath = [System.Environment]::GetEnvironmentVariable('PATH', [System.EnvironmentVariableTarget]::User)
if ($currentPath -notlike "*$binDir*") {
    $newPath = "$currentPath;$binDir"
    [System.Environment]::SetEnvironmentVariable('PATH', $newPath, [System.EnvironmentVariableTarget]::User)
    $env:Path = $newPath
}
```

5. Проверьте установку командой:
```powershell
zrok version
```

### 2. Подготовка проекта

1. Склонируйте проект и перейдите в его директорию
2. Скопируйте файл конфигурации:
```powershell
cp .env.example .env
```

3. Откройте файл `.env` и заполните обязательные переменные:

```env
# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=ваш_telegram_bot_token

# Zrok Configuration  
ZROK_AUTHTOKEN=ваш_zrok_auth_token
```

### 3. Получение необходимых токенов

#### Telegram Bot Token
1. Найдите в Telegram бота [@BotFather](https://t.me/botfather)
2. Отправьте команду `/newbot` для создания нового бота
3. Следуйте инструкциям для получения токена
4. Вставьте полученный токен в переменную `TELEGRAM_BOT_TOKEN`

#### Zrok Auth Token
1. Зарегистрируйтесь на [zrok.io](https://zrok.io)
2. Получите Auth Token в личном кабинете
3. Вставьте токен в переменную `ZROK_AUTHTOKEN`

### 4. Установка зависимостей

Выполните команды в корневой директории проекта:

```powershell
composer install
npm install
```

### 5. Запуск проекта

Запустите автоматизированный скрипт развертывания:

```powershell
.\start.ps1
```

Скрипт выполнит следующие действия:
- Запустит Docker контейнеры
- Выполнит миграции базы данных
- Создаст ключ приложения
- Создаст аккаунт администратора с данными:
  - **Email**: admin@admin.com
  - **Пароль**: admin
- Настроит Zrok туннелирование
- Выведет публичный URL для доступа к приложению

### 6. Настройка Webhook

После успешного запуска скрипта:

1. **Скопируйте публичный URL**, который выведет Zrok (например: `https://xyz123.share.zrok.io`)
2. **Добавьте URL в .env файл**:
   ```env
   TELEGRAM_WEBHOOK_DOMAIN=https://xyz123.share.zrok.io
   ```
3. **Установите webhook**, перейдя по адресу:
   ```
   GET: http://localhost:93/api/v1/telegram/set/webhook
   ```

### 7. Тестирование бота

После успешной установки webhook:
1. Найдите вашего бота в Telegram по имени, которое вы указали при создании
2. Отправьте команду `/start`
3. Бот должен зарегистрировать вас и отправить сообщение с кнопкой

## Альтернативное развертывание без Docker

Если вы предпочитаете развертывание без Docker контейнеров, следуйте данной инструкции. По умолчанию используется SQLite база данных, что упрощает локальную разработку.

### 1. Установка Zrok CLI

Следуйте инструкциям из основного раздела "1. Установка Zrok CLI" выше.

### 2. Подготовка проекта

1. Склонируйте проект и перейдите в его директорию
2. Скопируйте файл конфигурации:
```powershell
cp .env.example .env
```

3. Откройте файл `.env` и настройте переменные окружения:

```env
# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=ваш_telegram_bot_token

# Zrok Configuration  
ZROK_AUTHTOKEN=ваш_zrok_auth_token
```

### 3. Установка зависимостей

Выполните команды в корневой директории проекта:

```powershell
# Установка PHP зависимостей
composer install

# Установка JavaScript зависимостей
npm install
```

### 4. Настройка приложения

```powershell
# Генерация ключа приложения
php artisan key:generate

# Выполнение миграций
php artisan migrate --seed

# Создание аккаунта администратора
php artisan orchid:admin admin admin@admin.com admin

# Очистка кеша (если нужно)
php artisan optimize:clear
```

### 5. Запуск сервера разработки

```powershell
# Запуск Laravel сервера
php artisan serve
```

Приложение будет доступно по адресу: http://localhost:8000

### 6. Настройка Zrok туннелирования

В новой вкладке PowerShell выполните:

```powershell
# Авторизация в Zrok (выполняется один раз)
zrok enable your_zrok_auth_token

# Создание публичного туннеля
zrok share public http://localhost:8000
```

Скопируйте публичный URL, который выведет Zrok.

### 7. Настройка Webhook

1. **Добавьте публичный URL в .env файл**:
   ```env
   TELEGRAM_WEBHOOK_DOMAIN=https://xyz123.share.zrok.io
   ```

2. **Установите webhook**, перейдя по адресу:
   ```
   http://localhost:8000/api/v1/telegram/set/webhook
   ```

### 8. Тестирование

1. Найдите вашего бота в Telegram по имени, которое вы указали при создании
2. Отправьте команду `/start`
3. Бот должен зарегистрировать вас и отправить сообщение с кнопкой

### Остановка сервисов

Для остановки локального развертывания:
1. Остановите сервер Laravel (Ctrl+C в терминале с сервером)
2. Остановите Zrok туннель (Ctrl+C в терминале с Zrok)

## Порты и доступы

### С Docker (автоматический скрипт)
- **Локальное приложение**: http://localhost:93
- **Админ панель**: http://localhost:93 (login: admin@admin.com, password: admin)
- **Webhook setup**: http://localhost:93/api/v1/telegram/set/webhook

### Без Docker (ручная настройка)
- **Локальное приложение**: http://localhost:8000
- **Админ панель**: http://localhost:8000 (login: admin@admin.com, password: admin)
- **Webhook setup**: http://localhost:8000/api/v1/telegram/set/webhook

### Общие endpoints
- **Публичный доступ**: URL, предоставленный Zrok
- **Webhook endpoint**: `/api/v1/telegram/webhook`

## Устранение неполадок

### Ошибки Docker
- Убедитесь, что Docker Desktop запущен
- Проверьте, что порт 93 не занят другими приложениями

### Ошибки локального развертывания
- Убедитесь, что PHP версии 8.1+ установлен и доступен в PATH
- Проверьте, что все необходимые PHP расширения установлены (включая sqlite3)
- Для SQLite: убедитесь, что файл `database/database.sqlite` существует и доступен для записи
- Проверьте правильность настроек базы данных в `.env` файле
- Убедитесь, что порт 8000 не занят другими приложениями
- При ошибках миграций проверьте подключение к базе данных: `php artisan tinker` → `DB::connection()->getPdo()`
- Если есть проблемы с правами доступа к SQLite, проверьте права на папку `database/`

### Ошибки Zrok
- Проверьте правильность Auth Token
- Убедитесь, что Zrok CLI установлен и доступен в PATH
- При ошибке авторизации выполните: `zrok enable your_zrok_auth_token`

### Ошибки Webhook
- Убедитесь, что `TELEGRAM_WEBHOOK_DOMAIN` содержит корректный публичный URL
- Проверьте, что webhook установлен корректно через соответствующий endpoint
- При изменении URL в `.env` перезапустите сервер Laravel

### Проблемы с ботом
- Проверьте правильность `TELEGRAM_BOT_TOKEN`
- Убедитесь, что webhook успешно установлен
- Проверьте логи приложения: `storage/logs/laravel.log`
- Проверьте, что публичный URL доступен извне
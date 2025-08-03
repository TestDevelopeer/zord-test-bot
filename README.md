# Telegram Bot Laravel Project

Проект представляет собой Laravel-приложение с интеграцией Telegram бота, развертываемое в Docker контейнерах с использованием Zrok для публичного туннелирования.

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
4. Выполните следующие команды для установки:

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
- Создаст ключи приложения
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
   http://localhost:93/api/v1/telegram/set/webhook
   ```

### 7. Тестирование бота

После успешной установки webhook:
1. Найдите вашего бота в Telegram по имени, которое вы указали при создании
2. Отправьте команду `/start`
3. Бот должен зарегистрировать вас и отправить сообщение с кнопкой

## Порты и доступы

- **Локальное приложение**: http://localhost:93
- **Публичный доступ**: URL, предоставленный Zrok
- **Webhook endpoint**: `/api/v1/telegram/webhook`
- **Webhook setup**: `/api/v1/telegram/set/webhook`

## Устранение неполадок

### Ошибки Docker
- Убедитесь, что Docker Desktop запущен
- Проверьте, что порт 93 не занят другими приложениями

### Ошибки Zrok
- Проверьте правильность Auth Token
- Убедитесь, что Zrok CLI установлен и доступен в PATH

### Ошибки Webhook
- Убедитесь, что `TELEGRAM_WEBHOOK_DOMAIN` содержит корректный публичный URL
- Проверьте, что webhook установлен корректно через endpoint `/api/v1/telegram/set/webhook`

### Проблемы с ботом
- Проверьте правильность `TELEGRAM_BOT_TOKEN`
- Убедитесь, что webhook успешно установлен
- Проверьте логи приложения
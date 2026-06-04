# SMS Транш Генератор

Веб-приложение для создания SMS рассылок. Читает номера телефонов и шаблоны из Google Sheets, генерирует XLSX файл с траншем.

## Функции

- Читает неиспользованные номера из Google Sheets (Таблица 1, Лист 3, Столбец B)
- Читает шаблоны сообщений из Google Sheets (Таблица 2)
- Вы указываете сколько номеров взять (например: 2000)
- Генерирует XLSX: `Номер | Текст 1 | Текст 2 | Транш`
- Нумерация траншей начинается с **107** и автоматически инкрементируется
- После генерации помечает номера как использованные в Google Sheets

## Быстрый старт

```bash
bash setup.sh
```

Скрипт установит PHP, Composer, зависимости и запустит сервер.

## Ручная установка

### Требования
- PHP 8.1+
- Composer
- Google Sheets API credentials (Service Account)

### Шаги

```bash
# 1. Установить зависимости
composer install

# 2. Настроить окружение
cp .env.example .env
php artisan key:generate

# 3. Создать папки
mkdir -p storage/app/public storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
touch database/database.sqlite

# 4. Обновить DB_DATABASE в .env
# DB_DATABASE=/абсолютный/путь/к/database/database.sqlite

# 5. Добавить Google credentials (см. ниже)

# 6. Запустить
php artisan serve
```

Открыть: http://localhost:8000

## Настройка Google Sheets API

1. Перейдите на [Google Cloud Console](https://console.cloud.google.com/)
2. Создайте проект
3. Включите **Google Sheets API**: APIs & Services → Enable APIs
4. Создайте **Service Account**: APIs & Services → Credentials → Create Credentials → Service Account
5. Скачайте JSON ключ → сохраните как `google-credentials.json` в корне проекта
6. Поделитесь **обеими таблицами** с email сервисного аккаунта с правами **Редактора**

## Конфигурация .env

```env
GOOGLE_CREDENTIALS_PATH=/path/to/google-credentials.json

# Таблица 1 (номера телефонов)
PHONES_SPREADSHEET_ID=14k2qckLk59rA4sbVt86ljmwsbdZRYV8NQAHh2hEX_mE
PHONES_SHEET_NAME=Лист3
PHONES_COLUMN=B
PHONES_TRANCHE_COLUMN=D

# Таблица 2 (шаблоны)
TEMPLATES_SPREADSHEET_ID=11Hj1kWdI5b0FJFGMoYvayr9PwLjtl3hhBLaBTFKqFYk
TEMPLATES_SHEET_NAME=Лист1
TEMPLATES_TEXT1_COLUMN=B
TEMPLATES_TEXT2_COLUMN=C
TEMPLATES_TRANCHE_COLUMN=D

# Начальный номер транша
STARTING_TRANCHE=107
```

## Структура выходного XLSX

| Столбец | Содержимое | Источник |
|---------|-----------|----------|
| A | Номер телефона | Таблица 1, Лист 3, Столбец B |
| B | Текст 1 | Таблица 2, Столбец B |
| C | Текст 2 | Таблица 2, Столбец C |
| D | Номер транша | Генерируется автоматически |

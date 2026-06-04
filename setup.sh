#!/bin/bash

# SMS Tranche System - Setup Script
# Run: bash setup.sh

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo ""
echo "========================================"
echo "  SMS Транш Генератор — Установка"
echo "========================================"
echo ""

# 1. Check PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}PHP не найден. Установка через Homebrew...${NC}"
    if ! command -v brew &> /dev/null; then
        echo "Устанавливаем Homebrew..."
        /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
        # Add Homebrew to PATH (Apple Silicon)
        if [ -f /opt/homebrew/bin/brew ]; then
            eval "$(/opt/homebrew/bin/brew shellenv)"
        fi
    fi
    brew install php
    echo -e "${GREEN}PHP установлен!${NC}"
fi

echo -e "${GREEN}✓ PHP: $(php -v | head -1)${NC}"

# 2. Check Composer
if ! command -v composer &> /dev/null; then
    echo "Устанавливаем Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    echo -e "${GREEN}Composer установлен!${NC}"
fi

echo -e "${GREEN}✓ Composer: $(composer --version --no-ansi | head -1)${NC}"

# 3. Install PHP dependencies
echo ""
echo "Устанавливаем зависимости PHP..."
composer install --no-dev --optimize-autoloader

# 4. Setup .env
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
    echo -e "${GREEN}✓ .env создан и APP_KEY сгенерирован${NC}"
fi

# 5. Create storage directories
mkdir -p storage/app/public storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 6. Create SQLite DB placeholder
touch database/database.sqlite

# 7. Update DB path in .env
DB_PATH="$(pwd)/database/database.sqlite"
sed -i.bak "s|DB_DATABASE=.*|DB_DATABASE=${DB_PATH}|" .env && rm -f .env.bak

echo ""
echo -e "${YELLOW}========================================"
echo "  Осталось настроить Google Sheets API"
echo "========================================"
echo ""
echo "1. Перейдите в Google Cloud Console:"
echo "   https://console.cloud.google.com/"
echo ""
echo "2. Создайте проект (или выберите существующий)"
echo ""
echo "3. Включите Google Sheets API:"
echo "   APIs & Services → Enable APIs → поиск 'Google Sheets API' → Enable"
echo ""
echo "4. Создайте Service Account:"
echo "   APIs & Services → Credentials → Create Credentials → Service Account"
echo "   Дайте роль: Editor"
echo ""
echo "5. Скачайте JSON ключ:"
echo "   Credentials → ваш Service Account → Keys → Add Key → JSON"
echo "   Сохраните как: google-credentials.json в корне проекта"
echo ""
echo "6. Поделитесь таблицами с email сервисного аккаунта:"
echo "   (email будет вида: name@project.iam.gserviceaccount.com)"
echo "   Откройте каждую таблицу → Поделиться → вставьте email → Редактор"
echo ""
echo "7. Обновите .env если нужно изменить настройки листов:"
echo "   nano .env"
echo ""
echo -e "${NC}========================================"
echo ""

# 8. Start server
echo -e "${GREEN}Запускаем сервер на http://localhost:8000${NC}"
echo ""
php artisan serve

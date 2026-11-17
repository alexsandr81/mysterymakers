#!/usr/bin/env bash
# MysteryMakers: установка на Linux/macOS (нужны PHP 8.2+ и Composer)
set -euo pipefail

command -v composer >/dev/null || { echo "Composer не найден"; exit 1; }
command -v php >/dev/null || { echo "PHP не найден"; exit 1; }
[ -e shop ] && { echo 'Папка "shop" уже существует'; exit 1; }

composer create-project laravel/laravel shop
for d in app database lang public resources routes; do cp -R "$d/." "shop/$d/"; done

sed -i.bak -E 's/^APP_LOCALE=.*/APP_LOCALE=uk/; s/^APP_FALLBACK_LOCALE=.*/APP_FALLBACK_LOCALE=uk/; s/^APP_NAME=.*/APP_NAME=MysteryMakers/' shop/.env
rm -f shop/.env.bak

cd shop
php artisan migrate --seed --force
php artisan storage:link

echo
echo "Готово. Запуск: cd shop && php artisan serve  ->  http://127.0.0.1:8000"
echo "Админка: /admin   admin@mysterymakers.local / admin12345 (СМЕНИТЕ ПАРОЛЬ!)"

@echo off
chcp 65001 >nul
REM ============================================================
REM  MysteryMakers: установка на Windows (нужны PHP 8.2+ и Composer)
REM  Можно запускать откуда угодно: сайт создаётся в папке "shop"
REM  РЯДОМ с этим файлом.
REM ============================================================

REM Работаем в папке, где лежит сам скрипт (а не там, откуда его запустили)
cd /d "%~dp0"

where composer >nul 2>nul || (echo Composer не найден. Установите Laragon или Composer, см. README.md & pause & exit /b 1)
where php >nul 2>nul || (echo PHP не найден. Установите Laragon или PHP, см. README.md & pause & exit /b 1)

if exist shop (echo Папка "shop" уже существует. Удалите её или переименуйте. & pause & exit /b 1)

REM Проверяем, что файлы магазина на месте, ДО скачивания Laravel
for %%D in (app config database lang public resources routes tests) do (
    if not exist "%%D" (echo Не найдена папка "%%D" рядом со скриптом. Распакуйте проект целиком. & pause & exit /b 1)
)

echo [1/5] Скачиваю чистый Laravel...
call composer create-project laravel/laravel shop || (echo Ошибка установки Laravel & pause & exit /b 1)

echo [2/5] Копирую файлы магазина...
for %%D in (app config database lang public resources routes tests) do (
    xcopy /E /Y /I /Q "%%D" "shop\%%D" >nul || (echo Ошибка копирования %%D & pause & exit /b 1)
)

echo [3/5] Настраиваю язык (украинский по умолчанию)...
powershell -NoProfile -Command "(Get-Content shop\.env) -replace '^APP_LOCALE=.*','APP_LOCALE=uk' -replace '^APP_FALLBACK_LOCALE=.*','APP_FALLBACK_LOCALE=uk' -replace '^APP_NAME=.*','APP_NAME=MysteryMakers' | Set-Content -Encoding UTF8 shop\.env"

cd shop
echo [4/5] Создаю базу данных и демо-товары...
call php artisan migrate --seed --force || (echo Ошибка миграции & pause & exit /b 1)
call php artisan storage:link

echo [5/5] Готово!
echo.
echo   Запуск:   cd "%~dp0shop"  ^&^&  php artisan serve
echo   Сайт:     http://127.0.0.1:8000
echo   Админка:  http://127.0.0.1:8000/admin
echo   Вход:     admin@mysterymakers.local / admin12345   (СМЕНИТЕ ПАРОЛЬ!)
echo.
pause

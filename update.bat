@echo off
chcp 65001 >nul
REM ============================================================
REM  MysteryMakers: обновление УЖЕ установленного сайта.
REM  Копирует новые файлы магазина в Laravel и применяет миграции.
REM  Данные в базе (товары, заказы, пользователи) не удаляются.
REM
REM  Использование:
REM     update.bat "C:\laragon\www\shop"
REM  Без аргумента ищет сайт в папке "shop" рядом со скриптом.
REM ============================================================

cd /d "%~dp0"

set "TARGET=%~1"
if "%TARGET%"=="" set "TARGET=%~dp0shop"

if not exist "%TARGET%\artisan" (
    echo Не найден сайт Laravel в "%TARGET%".
    echo Укажите путь:  update.bat "C:\laragon\www\shop"
    pause
    exit /b 1
)

for %%D in (app config database lang public resources routes) do (
    if not exist "%%D" (echo Не найдена папка "%%D" рядом со скриптом. & pause & exit /b 1)
)

echo [1/3] Копирую новые файлы в "%TARGET%"...
for %%D in (app config database lang public resources routes) do (
    xcopy /E /Y /I /Q "%%D" "%TARGET%\%%D" >nul || (echo Ошибка копирования %%D & pause & exit /b 1)
)

cd /d "%TARGET%"

echo [2/3] Применяю миграции базы данных...
call php artisan migrate --force || (echo Ошибка миграции & pause & exit /b 1)

echo [3/3] Добавляю демо-страницы и характеристики (существующее не перезаписывается)...
call php artisan db:seed --class=ContentSeeder --force
call php artisan view:clear
call php artisan route:clear

echo.
echo Готово. Запустите сайт:  php artisan serve
echo.
pause

@echo off
chcp 65001 >nul
title VAST STUDIO - Local PHP Server
color 0A

echo ===================================================================
echo               KHOI DONG MAY CHU PHP VAST STUDIO
echo        (Chay truc tiep PHP, khong can bat phan mem Laragon)
echo ===================================================================
echo.

cd /d "%~dp0"

set "PHP_BIN=php"
if exist "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" (
    set "PHP_BIN=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
) else if exist "C:\laragon\bin\php" (
    for /d %%i in (C:\laragon\bin\php\*) do (
        if exist "%%i\php.exe" set "PHP_BIN=%%i\php.exe"
    )
)

"%PHP_BIN%" -v >nul 2>nul
if %errorlevel% neq 0 (
    echo [THONG BAO] Khong tim thay PHP, dang chuyen sang Node.js server...
    where node >nul 2>nul
    if %errorlevel% equ 0 (
        start "" "http://localhost:3000/index.php"
        node server.js
        exit /b
    )
    color 0C
    echo [LOI] Khong tim thay ca PHP va Node.js tren may!
    pause
    exit /b
)

echo [1/2] Dang mo trinh duyet web tai: http://localhost:8000/index.php ...
timeout /t 1 >nul
start "" "http://localhost:8000/index.php"

echo [2/2] Dang chay PHP Built-in Server tai cong 8000...
echo.
echo ===================================================================
echo   • Trang Chu:      http://localhost:8000/index.php
echo   • San Pham:       http://localhost:8000/products.php
echo   • Thanh Toan:     http://localhost:8000/checkout.php
echo   • Tai Khoan:      http://localhost:8000/auth.php
echo   • Quan Tri:       http://localhost:8000/admin.php
echo ===================================================================
echo   Nhan to hop Ctrl + C de dung server.
echo.

"%PHP_BIN%" -S localhost:8000
pause

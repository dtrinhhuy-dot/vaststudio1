@echo off
chcp 65001 >nul
title VAST STUDIO Local Server
color 0A

echo ===================================================================
echo               KHOI DONG SERVER VAST STUDIO
echo        (Khong can bat Laragon, XAMPP hay phan mem thu 3)
echo ===================================================================
echo.

cd /d "%~dp0"

where node >nul 2>nul
if %errorlevel% neq 0 (
    color 0C
    echo [LOI] Khong tim thay Node.js tren may tinh cua ban!
    echo Vui long cai dat Node.js tu trang: https://nodejs.org
    echo.
    pause
    exit /b
)

echo [1/2] Dang mo trinh duyet web den http://localhost:3000 ...
timeout /t 1 >nul
start "" "http://localhost:3000"

echo [2/2] Dang chay server noi bo...
echo (De dung may chu, dong cua so nay hoac nhan to hop Ctrl + C)
echo.
node server.js

pause

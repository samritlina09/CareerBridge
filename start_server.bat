@echo off
title CareerBridge Placement Server
echo ============================================================
echo   Starting CareerBridge Placement Management System
echo ============================================================
echo.

REM Check if 'php' is in PATH
where php >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] Using PHP from PATH
    echo Starting server at http://localhost:8000 ...
    echo Press Ctrl+C to stop the server.
    echo.
    php -S localhost:8000 router.php
    goto end
)

REM Check default XAMPP PHP path
if exist "C:\xampp\php\php.exe" (
    echo [OK] Found XAMPP PHP at C:\xampp\php\php.exe
    echo Starting server at http://localhost:8000 ...
    echo Press Ctrl+C to stop the server.
    echo.
    "C:\xampp\php\php.exe" -S localhost:8000 router.php
    goto end
)

echo [ERROR] PHP was not found in PATH or C:\xampp\php\php.exe.
echo Please start Apache via XAMPP Control Panel or install PHP.
echo.
pause

:end

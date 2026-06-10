@echo off
setlocal

echo === PhonesDukan XAMPP Fix ===
echo.

echo [1/6] Disabling conflicting Windows MySQL services...
for %%S in (MySQL80 MySQL800 MySQL81) do (
    sc query "%%S" >nul 2>&1 && (
        net stop "%%S" >nul 2>&1
        sc config "%%S" start= disabled >nul 2>&1
        echo   - Disabled %%S
    )
)

echo [2/6] Stopping stale Apache/MySQL processes...
taskkill /F /IM httpd.exe >nul 2>&1
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul

echo [3/6] Removing stale PID lock files...
if exist "C:\xampp\apache\logs\httpd.pid" del /F /Q "C:\xampp\apache\logs\httpd.pid"
if exist "C:\xampp\mysql\data\mysql.pid" del /F /Q "C:\xampp\mysql\data\mysql.pid"
for %%F in ("C:\xampp\mysql\data\*.pid") do del /F /Q "%%F" 2>nul

echo [4/6] Starting MySQL...
start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini"
timeout /t 5 /nobreak >nul

echo [5/6] Repairing MySQL system tables...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "REPAIR TABLE mysql.db;" >nul 2>&1
"C:\xampp\mysql\bin\mysqlcheck.exe" -u root --repair --databases mysql >nul 2>&1
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS u903950600_custom_pd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>&1

echo [6/6] Starting Apache and checking health...
start "" /B "C:\xampp\apache\bin\httpd.exe"
timeout /t 2 /nobreak >nul

"C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT 1;" >nul
if %errorlevel%==0 (echo   - MySQL: OK) else (echo   - MySQL: FAILED)

curl -s http://localhost/phonesdukan/ >nul
if %errorlevel%==0 (echo   - Apache: OK) else (echo   - Apache: FAILED)

echo.
echo Done.
echo Site: http://localhost/phonesdukan/
echo Admin: http://localhost/phonesdukan/admin/login.php
echo.
echo If MySQL still fails in XAMPP Control Panel:
echo 1. Click Stop on MySQL, wait 3 seconds, then Start again
echo 2. Run this script as Administrator
echo 3. Optional orphan DB cleanup (old deleted projects):
echo    C:\xampp\mysql\bin\mysql.exe -u root ^< C:\xampp\mysql\scripts\cleanup_orphan_innodb.sql
echo.
pause

@echo off
echo Disabling Windows MySQL services that conflict with XAMPP on port 3306...
echo Run this file as Administrator.
echo.

for %%S in (MySQL80 MySQL800 MySQL81) do (
    sc query "%%S" >nul 2>&1 && (
        net stop "%%S" >nul 2>&1
        sc config "%%S" start= disabled
        echo Disabled %%S
    )
)

echo.
echo Done. Restart XAMPP Control Panel and start MySQL.
pause

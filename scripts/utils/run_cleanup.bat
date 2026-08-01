@echo off
cd /d "%~dp0..\.."
C:\laragon\bin\php\php-8.3.24-Win32-vs16-x64\php.exe scripts\utils\cleanup_guest.php >> storage\logs\cleanup.log 2>&1

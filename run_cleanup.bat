@echo off
cd /d "c:\xampp\htdocs\x"
c:\xampp\php\php.exe cleanup_guest.php >> logs\cleanup.log 2>&1

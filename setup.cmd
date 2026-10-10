@echo off
setlocal
cd /d "%~dp0"
where php >nul 2>&1
if errorlevel 1 (
  echo Open this script from the OpenServer command line after activating PHP-8.3 and MySQL-8.4.
  pause
  exit /b 1
)
if not exist wordpress\wp-config.php (
  php provision.php
  if errorlevel 1 goto failed
)
php wp-cli.phar core is-installed --path=wordpress >nul 2>&1
if errorlevel 1 (
  set /p VOLT_ADMIN_EMAIL=Administrator email: 
  powershell -NoProfile -Command "$p=-join ((48..57)+(65..90)+(97..122)|Get-Random -Count 24|ForEach-Object {[char]$_}); Set-Content -LiteralPath 'admin-password.local.txt' -Value $p -Encoding ASCII"
  set /p VOLT_ADMIN_PASSWORD=<admin-password.local.txt
  call :install
  if errorlevel 1 goto failed
)
php wp-cli.phar option update home http://electronics.local --path=wordpress
if errorlevel 1 goto failed
php wp-cli.phar option update siteurl http://electronics.local --path=wordpress
if errorlevel 1 goto failed
php wp-cli.phar plugin activate woocommerce ga-google-analytics --path=wordpress
if errorlevel 1 goto failed
php wp-cli.phar theme activate volt --path=wordpress
if errorlevel 1 goto failed
php wp-cli.phar eval-file seed.php --path=wordpress
if errorlevel 1 goto failed
php wp-cli.phar core verify-checksums --path=wordpress
if errorlevel 1 goto failed
echo Site ready: http://electronics.local
echo Admin: http://electronics.local/wp-admin/ Login: volt_admin
echo Password is in admin-password.local.txt. Keep this file private.
echo Google Analytics remains inactive until a real GA4 Measurement ID is added.
pause
exit /b 0
:install
php wp-cli.phar core install --path=wordpress --url=http://electronics.local --title="VOLT Electronics" --admin_user=volt_admin --admin_password="%VOLT_ADMIN_PASSWORD%" --admin_email="%VOLT_ADMIN_EMAIL%" --skip-email
exit /b %errorlevel%
:failed
echo Setup failed. Read the error above; existing content was not deleted.
pause
exit /b 1

@echo off
echo 🚀 Starting SMS Backend deployment...

REM Check if PHP is installed
php --version >nul 2>&1
if errorlevel 1 (
    echo ❌ PHP is not installed. Please install PHP 8.1 or higher.
    pause
    exit /b 1
)

REM Check if Composer is installed
composer --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Composer is not installed. Please install Composer.
    pause
    exit /b 1
)

echo ✅ PHP and Composer are available

REM Install dependencies
echo 📦 Installing Composer dependencies...
composer install --no-dev --optimize-autoloader

REM Create .env file if it doesn't exist
if not exist .env (
    echo 📝 Creating .env file from example...
    copy .env.example .env
)

REM Generate application key
echo 🔑 Generating application key...
php artisan key:generate --no-interaction

REM Run migrations
echo 🗄️ Running database migrations...
php artisan migrate --force

REM Seed database
echo 🌱 Seeding database...
php artisan db:seed --force

REM Clear and cache config
echo 🧹 Clearing and caching configuration...
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ✅ Deployment completed successfully!
echo 🌐 Your SMS Backend API is ready to use!
echo 📖 Check the README.md file for API documentation
pause

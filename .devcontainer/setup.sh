#!/bin/bash
set -e

echo "🚀 Starting Laravel development environment setup..."

# Install PHP MySQL extension (for Docker PHP images)
echo "📦 Installing PHP MySQL extension..."
if command -v docker-php-ext-install &> /dev/null; then
    # Compile the extension
    sudo docker-php-ext-install pdo_mysql || true
    
    # Manually enable it (docker-php-ext-enable has path issues)
    echo "extension=pdo_mysql.so" | sudo tee /usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini
else
    # Fallback if docker-php-ext-install not available
    echo "extension=pdo_mysql.so" | sudo tee /usr/local/etc/php/conf.d/pdo_mysql.ini
fi

# Disable Xdebug for CLI to avoid warnings
echo "🔧 Configuring Xdebug..."
if [ -f /usr/local/etc/php/conf.d/xdebug.ini ]; then
    echo "xdebug.mode=off" | sudo tee -a /usr/local/etc/php/conf.d/xdebug.ini
fi

# Verify PDO MySQL is loaded
echo "✅ Verifying PDO MySQL installation..."
if php -m | grep -q pdo_mysql; then
    echo "✓ PDO MySQL extension loaded successfully"
else
    echo "⚠ Warning: PDO MySQL extension may not be loaded properly"
fi

# Install MariaDB (MySQL compatible)
echo "📦 Installing MariaDB..."
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y mariadb-server mariadb-client
sudo service mariadb start

# Wait for MariaDB to fully start
echo "⏳ Waiting for MariaDB to start..."
sleep 3

# Configure MariaDB
echo "🔧 Configuring MariaDB..."
sudo mariadb -e "CREATE DATABASE IF NOT EXISTS laravel;"
sudo mariadb -e "CREATE USER IF NOT EXISTS 'laravel'@'localhost' IDENTIFIED BY 'password';"
sudo mariadb -e "GRANT ALL PRIVILEGES ON laravel.* TO 'laravel'@'localhost';"
sudo mariadb -e "FLUSH PRIVILEGES;"

# Check if Laravel is already installed
if [ ! -f "composer.json" ]; then
    echo "📥 Installing Laravel..."
    composer create-project laravel/laravel temp-laravel
    shopt -s dotglob
    mv temp-laravel/* .
    rmdir temp-laravel
else
    echo "✅ Laravel already exists, running composer install..."
    composer install
fi

# Setup environment file
if [ ! -f ".env" ]; then
    echo "⚙️  Setting up .env file..."
    cp .env.example .env
    php artisan key:generate
fi

# Update database configuration in .env
echo "🗄️  Configuring database connection..."
sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=127.0.0.1/' .env
sed -i 's/# DB_PORT=3306/DB_PORT=3306/' .env
sed -i 's/# DB_DATABASE=laravel/DB_DATABASE=laravel/' .env
sed -i 's/# DB_USERNAME=root/DB_USERNAME=laravel/' .env
sed -i 's/# DB_PASSWORD=/DB_PASSWORD=password/' .env

# Install Node dependencies if package.json exists
if [ -f "package.json" ]; then
    echo "📦 Installing Node dependencies..."
    npm install
fi

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force

echo ""
echo "✨ Setup complete!"
echo ""
echo "🎯 Quick start commands:"
echo "  - Start Laravel: php artisan serve"
echo "  - Start Vite: npm run dev"
echo "  - Run migrations: php artisan migrate"
echo "  - Create controller: php artisan make:controller YourController"
echo ""
echo "📝 Database credentials:"
echo "  - Database: laravel"
echo "  - Username: laravel"
echo "  - Password: password"
echo "  - Host: localhost (or 127.0.0.1)"
echo ""
echo "🚀 Your Laravel app is ready to rock!"
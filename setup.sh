#!/bin/bash
set -e

echo "🚀 Starting Laravel development environment setup..."

# Install MySQL
echo "📦 Installing MySQL..."
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server
sudo service mysql start

# Configure MySQL
echo "🔧 Configuring MySQL..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS laravel;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'laravel'@'localhost' IDENTIFIED BY 'password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON laravel.* TO 'laravel'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

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
php artisan migrate

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
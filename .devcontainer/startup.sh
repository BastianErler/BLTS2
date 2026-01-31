#!/bin/bash
set -e

echo "🔄 Starting services..."

# Check if composer dependencies exist
if [ ! -d "vendor" ]; then
    echo "📦 Installing composer dependencies..."
    composer install
fi

# Check if node_modules exist
if [ ! -d "node_modules" ] && [ -f "package.json" ]; then
    echo "📦 Installing npm dependencies..."
    npm install
fi

# Ensure .env exists
if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    echo "⚙️  Creating .env file..."
    cp .env.example .env
    php artisan key:generate
fi

# Check if MariaDB is installed
if command -v mariadb &> /dev/null; then
    # Start MariaDB if not running
    if ! sudo service mariadb status > /dev/null 2>&1; then
        echo "🔧 Starting MariaDB..."
        sudo service mariadb start
        sleep 2
    fi
    
    # Ensure database exists
    sudo mariadb -e "CREATE DATABASE IF NOT EXISTS laravel;" 2>/dev/null || true
    echo "✅ MariaDB running"
else
    echo "⚠️  MariaDB not installed. Run: bash .devcontainer/setup.sh"
fi

echo "✅ Services ready!"
echo ""
echo "💡 Quick commands:"
echo "   php artisan serve    # Start Laravel"
echo "   npm run dev          # Start Vite"
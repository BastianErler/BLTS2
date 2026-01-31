#!/bin/bash
set -e

echo "🎯 Setting up Vue SPA + Laravel API for Tippspiel..."

# Install Laravel Sanctum for API authentication
echo "🔐 Installing Laravel Sanctum (API Auth)..."
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# Install CORS support (for SPA communication)
echo "🌐 Configuring CORS..."
# Laravel 11+ has CORS built-in, just need to configure it

# Option 1: Standalone Vue SPA (recommended for learning)
echo ""
echo "Choose your frontend setup:"
echo "1) Standalone Vue SPA (separate frontend, recommended)"
echo "2) Inertia.js (Vue integrated with Laravel)"
read -p "Enter choice (1 or 2): " choice

if [ "$choice" == "2" ]; then
    # Install Inertia.js
    echo "📦 Installing Inertia.js..."
    composer require inertiajs/inertia-laravel
    npm install @inertiajs/vue3
    npm install @vitejs/plugin-vue
    
    echo "✅ Inertia.js installed! Check docs: https://inertiajs.com"
else
    # Standalone Vue setup
    echo "📦 Installing Vue 3 + Router + Pinia..."
    npm install vue@latest
    npm install vue-router@latest
    npm install pinia
    npm install @vitejs/plugin-vue
    npm install axios
    
    # Create basic Vue structure
    echo "📁 Creating Vue app structure..."
    mkdir -p resources/vue/src/{components,views,stores,services,router}
    mkdir -p resources/vue/public
    
    # Create main.js
    cat > resources/vue/src/main.js << 'EOF'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import './style.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
EOF

    # Create App.vue
    cat > resources/vue/src/App.vue << 'EOF'
<template>
  <div id="app">
    <RouterView />
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router'
</script>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
EOF

    # Create router
    cat > resources/vue/src/router/index.js << 'EOF'
import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
EOF

    # Create Home view
    cat > resources/vue/src/views/Home.vue << 'EOF'
<template>
  <div class="home">
    <h1>🎯 Tippspiel</h1>
    <p>Welcome to your betting app!</p>
  </div>
</template>

<script setup>
</script>

<style scoped>
.home {
  text-align: center;
  padding: 2rem;
}
</style>
EOF

    # Create API service
    cat > resources/vue/src/services/api.js << 'EOF'
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
})

// Add CSRF token
api.interceptors.request.use(config => {
  const token = document.querySelector('meta[name="csrf-token"]')?.content
  if (token) {
    config.headers['X-CSRF-TOKEN'] = token
  }
  return config
})

export default api
EOF

    # Create style.css
    cat > resources/vue/src/style.css << 'EOF'
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}
EOF

    # Create index.html
    cat > resources/vue/index.html << 'EOF'
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/vite.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tippspiel</title>
  </head>
  <body>
    <div id="app"></div>
    <script type="module" src="/src/main.js"></script>
  </body>
</html>
EOF

    # Create vite.config.js for Vue
    cat > vite.config.js << 'EOF'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    vue(),
    laravel({
      input: ['resources/vue/src/main.js'],
      refresh: true,
    }),
  ],
  root: 'resources/vue',
  build: {
    outDir: '../../public/build',
    emptyOutDir: true,
    manifest: true,
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    hmr: {
      host: 'localhost'
    }
  }
})
EOF

    echo "✅ Vue SPA structure created!"
fi

# Update .env for SPA
echo "⚙️  Updating .env for SPA..."
if ! grep -q "SANCTUM_STATEFUL_DOMAINS" .env; then
    echo "" >> .env
    echo "SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173" >> .env
    echo "SESSION_DRIVER=cookie" >> .env
    echo "SESSION_DOMAIN=localhost" >> .env
fi

echo ""
echo "✨ Setup complete!"
echo ""
echo "📝 Next steps:"
echo ""
echo "1. Start Laravel API:"
echo "   php artisan serve"
echo ""
echo "2. Start Vue dev server:"
echo "   npm run dev"
echo ""
echo "3. API will be available at: http://localhost:8000/api"
echo "4. Vue SPA will be at: http://localhost:5173"
echo ""
echo "🔧 Useful commands:"
echo "   php artisan make:model Match -m    # Create models"
echo "   php artisan make:controller Api/MatchController --api"
echo "   php artisan route:list              # Show all routes"
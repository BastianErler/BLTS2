# Laravel Development Setup

## 🚀 Installation

### 1. Dateien in dein Repo kopieren

Erstelle einen `.devcontainer` Ordner und kopiere beide Dateien hinein:

```
.devcontainer/
├── devcontainer.json
└── setup.sh
```

### 2. Codespace erstellen

- Geh auf GitHub zu deinem Repo
- Klick auf "Code" → "Codespaces" → "Create codespace on main"
- Warte, bis alles installiert ist (dauert ~3-5 Minuten beim ersten Mal)

### 3. Laravel starten

Nach dem Setup:

```bash
# Laravel Server starten
php artisan serve

# In einem neuen Terminal: Vite für Frontend starten
npm run dev
```

Deine Laravel App läuft dann auf Port 8000!

## 📦 Was ist installiert?

- **PHP 8.3** mit Composer
- **Node 20** mit npm
- **MySQL 8.0** 
- **Laravel** (neueste Version)

## 🗄️ Datenbank

Die Datenbank ist bereits konfiguriert:

- **Database:** `laravel`
- **User:** `laravel`
- **Password:** `password`
- **Host:** `localhost`

MySQL startet automatisch mit dem Container.

## 🛠️ Wichtige Befehle

```bash
# Artisan Befehle
php artisan migrate              # Migrations ausführen
php artisan make:controller Name # Controller erstellen
php artisan make:model Name      # Model erstellen
php artisan tinker               # REPL öffnen

# Composer
composer require package-name    # Package installieren
composer dump-autoload          # Autoloader neu laden

# NPM
npm install                     # Dependencies installieren
npm run dev                     # Development Server
npm run build                   # Production Build
```

## 🔧 Troubleshooting

### MySQL verbindet nicht
```bash
sudo service mysql start
sudo mysql -u laravel -p laravel  # Password: password
```

### Port 8000 bereits belegt
```bash
php artisan serve --port=8001
```

### Permissions Error
```bash
sudo chown -R vscode:vscode /workspaces/BLTS2
```

## 📝 VS Code Extensions

Folgende Extensions werden automatisch installiert:
- PHP Intelephense (Code-Vervollständigung)
- Laravel Blade (Syntax Highlighting)
- Prettier (Code Formatting)
- Tailwind CSS IntelliSense

## 🎯 Next Steps

1. Öffne `routes/web.php` und erstelle deine ersten Routes
2. Erstelle Views in `resources/views/`
3. Nutze `php artisan` für Code-Generierung
4. Check die offizielle Laravel Docs: https://laravel.com/docs

## ⚡ Performance-Tipps

- Der Container startet MySQL automatisch
- Vite Hot Module Replacement funktioniert out-of-the-box
- Composer Cache ist aktiviert für schnellere Installs
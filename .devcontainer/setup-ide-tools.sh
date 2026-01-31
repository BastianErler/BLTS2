#!/bin/bash
set -e

echo "🔧 Installing Laravel IDE Helper & TypeScript Transformer..."

# Install IDE Helper
echo "📦 Installing Laravel IDE Helper..."
composer require --dev barryvdh/laravel-ide-helper

# Publish IDE Helper config
php artisan vendor:publish --provider="Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider" --tag=config

# Generate helper files
echo "📝 Generating IDE Helper files..."
php artisan ide-helper:generate
php artisan ide-helper:models --nowrite
php artisan ide-helper:meta

# Install TypeScript Transformer
echo "📦 Installing TypeScript Transformer..."
composer require spatie/laravel-typescript-transformer --dev

# Publish TypeScript Transformer config
php artisan vendor:publish --provider="Spatie\TypeScriptTransformer\TypeScriptTransformerServiceProvider" --tag="config"

# Update .gitignore
echo "📝 Updating .gitignore..."
if ! grep -q "_ide_helper.php" .gitignore; then
    cat >> .gitignore << 'EOF'

# IDE Helper
_ide_helper.php
_ide_helper_models.php
.phpstorm.meta.php
EOF
fi

# Create TypeScript output directory
mkdir -p resources/vue/src/types

# Update TypeScript Transformer config
echo "⚙️  Configuring TypeScript Transformer..."
cat > config/typescript-transformer.php << 'EOF'
<?php

return [
    /*
     * The default output path for generated TypeScript files.
     */
    'output_path' => base_path('resources/vue/src/types/generated.d.ts'),

    /*
     * Collectors are responsible for finding classes that need to be transformed.
     */
    'collectors' => [
        Spatie\TypeScriptTransformer\Collectors\DefaultCollector::class,
    ],

    /*
     * Transformers are responsible for transforming PHP types to TypeScript.
     */
    'transformers' => [
        Spatie\TypeScriptTransformer\Transformers\MyclabsEnumTransformer::class,
        Spatie\TypeScriptTransformer\Transformers\DtoTransformer::class,
        Spatie\TypeScriptTransformer\Transformers\SpatieStateTransformer::class,
    ],

    /*
     * The default TypeScript type to use when no type is found.
     */
    'default_type_replacements' => [
        //
    ],

    /*
     * Should we automatically discover transformers in the app?
     */
    'auto_discover_transformers' => true,

    /*
     * The writer is responsible for writing the generated TypeScript to disk.
     */
    'writer' => Spatie\TypeScriptTransformer\Writers\TypeDefinitionWriter::class,
];
EOF

# Add composer scripts for IDE helper
echo "📝 Adding composer scripts..."
if ! grep -q "ide-helper:generate" composer.json; then
    composer config --global --no-interaction allow-plugins.barryvdh/laravel-ide-helper true
    
    # Note: These need to be added manually or via composer config
    echo ""
    echo "⚠️  Add these scripts to your composer.json manually:"
    echo ""
    cat << 'EOF'
"scripts": {
    "post-update-cmd": [
        "@php artisan ide-helper:generate",
        "@php artisan ide-helper:meta"
    ],
    "ide-helper": [
        "@php artisan ide-helper:generate",
        "@php artisan ide-helper:models --nowrite",
        "@php artisan ide-helper:meta"
    ],
    "types": [
        "@php artisan typescript:transform"
    ]
}
EOF
fi

echo ""
echo "✨ Installation complete!"
echo ""
echo "📝 Available commands:"
echo "  composer ide-helper              # Generate IDE helper files"
echo "  php artisan ide-helper:generate  # Generate IDE helper"
echo "  php artisan ide-helper:models    # Generate model PHPDocs"
echo "  php artisan ide-helper:meta      # Generate PhpStorm meta"
echo "  php artisan typescript:transform # Generate TypeScript types"
echo ""
echo "💡 Usage examples:"
echo ""
echo "  1. Add @typescript to your models:"
echo "     use Spatie\TypeScriptTransformer\Attributes\TypeScript;"
echo ""
echo "     #[TypeScript]"
echo "     class User extends Model { ... }"
echo ""
echo "  2. Generate types:"
echo "     php artisan typescript:transform"
echo ""
echo "  3. Import in Vue:"
echo "     import type { User } from '@/types/generated'"
echo ""

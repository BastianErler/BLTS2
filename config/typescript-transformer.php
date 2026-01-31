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

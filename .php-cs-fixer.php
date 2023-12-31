<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in(__DIR__)
            ->append([__FILE__])
            ->notPath([
                'src/Bundles/JsonFetcher/DependencyInjection/Configuration.php',
                'src/Bundles/JsonFetcher/Resources/config/services.php',
                'src/Bundles/Jwt/DependencyInjection/Configuration.php',
                'src/Bundles/Jwt/Resources/config/services.php',
                'src/Bundles/JwtFetcher/DependencyInjection/Configuration.php',
                'src/Bundles/JwtFetcher/Resources/config/services.php',
                'src/Bundles/Redis/DependencyInjection/Configuration.php',
                'src/Bundles/Redis/Resources/config/services.php',
                'src/Bundles/Singlea/DependencyInjection/Configuration.php',
                'src/Bundles/Singlea/Resources/config/services.php',
            ])
    )
    ->setRiskyAllowed(true)
    ->setRules([
        // base presets
        '@PER-CS' => true,
        '@PhpCsFixer' => true,
        '@Symfony' => true,
        '@PHP81Migration' => true,

        // risky presets
        '@PER-CS:risky' => true,
        '@PhpCsFixer:risky' => true,
        '@Symfony:risky' => true,
        '@PHP80Migration:risky' => true,

        // presets tuning
        'blank_line_after_opening_tag' => false,
        'blank_line_before_statement' => [
            'statements' => ['case', 'default', 'declare', 'return', 'throw', 'try'],
        ],
        'comment_to_phpdoc' => [
            'ignored_tags' => [
                'phan-suppress-current-line',
                'phan-suppress-next-line',
                'see',
                'todo',
            ],
        ],
        'linebreak_after_opening_tag' => false,
        'method_argument_space' => [
            'attribute_placement' => 'standalone',
            'on_multiline' => 'ignore',
        ],
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'new_line_for_chained_calls',
        ],
        'no_extra_blank_lines' => [
            'tokens' => [
                'break',
                'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'switch',
                'throw',
            ],
        ],
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'allow_unused_params' => true,
        ],
        'ordered_class_elements' => false,
        'ordered_imports' => [
            'imports_order' => [
                'const',
                'class',
                'function',
            ],
        ],
        'phpdoc_separation' => false,
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => false,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'single_line_comment_style' => [
            'comment_types' => [
                'asterisk',
            ],
        ],
        'single_line_throw' => false,
        'yoda_style' => false,

        // no-preset rules
        'date_time_immutable' => true,
        'final_class' => true,
        'header_comment' => [
            'header' => 'SPDX-License-Identifier: BSD-3-Clause',
            'location' => 'after_open',
            'separate' => 'bottom',
        ],
        'nullable_type_declaration_for_default_null_value' => true,
        'self_static_accessor' => true,
        'simplified_null_return' => true,
        'single_line_empty_body' => true,
        'static_lambda' => true,
    ])
;

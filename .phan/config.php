<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

use Phan\Issue;

define('ROOT_DIR', dirname(__DIR__));

return [
    'target_php_version' => '8.1',

    'allow_missing_properties' => false,

    'null_casts_as_any_type' => false,
    'null_casts_as_array' => false,
    'array_casts_as_null' => false,

    'scalar_implicit_cast' => false,
    'scalar_array_key_cast' => false,
    'scalar_implicit_partial' => [],

    'strict_method_checking' => true,
    'strict_object_checking' => true,
    'strict_param_checking' => true,
    'strict_property_checking' => true,
    'strict_return_checking' => true,

    'ignore_undeclared_variables_in_global_scope' => false,
    'ignore_undeclared_functions_with_known_signatures' => false,

    'backward_compatibility_checks' => false,

    'check_docblock_signature_return_type_match' => true,
    'phpdoc_type_mapping' => [],

    'dead_code_detection' => false,
    'unused_variable_detection' => true,
    'redundant_condition_detection' => true,

    'assume_real_types_for_internal_functions' => true,

    'quick_mode' => false,

    'globals_type_map' => [],

    'minimum_severity' => Issue::SEVERITY_LOW,
    'suppress_issue_types' => [
        Issue::TypeInvalidThrowsIsInterface,
        Issue::UnusedProtectedFinalMethodParameter,
        Issue::UnusedPublicFinalMethodParameter,
        Issue::UnusedPublicMethodParameter,
    ],

    'exclude_file_regex' => '@(src/Bundles|vendor)/.*/(t|Tests?)/@',
    'exclude_file_list' => [
        ROOT_DIR.'/src/Bundles/Singlea/Resources/config/routes.php',
    ],
    'exclude_analysis_directory_list' => [
        ROOT_DIR.'/vendor/',
    ],

    'enable_include_path_checks' => true,
    'processes' => 1,
    'analyzed_file_extensions' => [
        'php',
    ],
    'autoload_internal_extension_signatures' => [],

    'plugins' => [
        'AlwaysReturnPlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'DuplicateExpressionPlugin',
        'EmptyStatementListPlugin',
        'LoopVariableReusePlugin',
        'PHPDocRedundantPlugin',
        'PHPDocToRealTypesPlugin',
        'PossiblyStaticMethodPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
        'RedundantAssignmentPlugin',
        'RemoveDebugStatementPlugin',
        'SleepCheckerPlugin',
        'SimplifyExpressionPlugin',
        'StrictComparisonPlugin',
        'UnreachableCodePlugin',
        'UseReturnValuePlugin',
        'WhitespacePlugin',

        'UnusedSuppressionPlugin',
    ],

    'directory_list' => [
        ROOT_DIR.'/src',
        ROOT_DIR.'/vendor/predis/predis/src',
        ROOT_DIR.'/vendor/psr/cache',
        ROOT_DIR.'/vendor/psr/log',
        ROOT_DIR.'/vendor/snc/redis-bundle',
        ROOT_DIR.'/vendor/symfony/cache',
        ROOT_DIR.'/vendor/symfony/cache-contracts',
        ROOT_DIR.'/vendor/symfony/config',
        ROOT_DIR.'/vendor/symfony/console',
        ROOT_DIR.'/vendor/symfony/dependency-injection',
        ROOT_DIR.'/vendor/symfony/error-handler',
        ROOT_DIR.'/vendor/symfony/event-dispatcher',
        ROOT_DIR.'/vendor/symfony/event-dispatcher-contracts',
        ROOT_DIR.'/vendor/symfony/expression-language',
        ROOT_DIR.'/vendor/symfony/framework-bundle',
        ROOT_DIR.'/vendor/symfony/http-client',
        ROOT_DIR.'/vendor/symfony/http-client-contracts',
        ROOT_DIR.'/vendor/symfony/http-foundation',
        ROOT_DIR.'/vendor/symfony/http-kernel',
        ROOT_DIR.'/vendor/symfony/routing',
        ROOT_DIR.'/vendor/symfony/security-bundle',
        ROOT_DIR.'/vendor/symfony/security-core',
        ROOT_DIR.'/vendor/symfony/security-http',
        ROOT_DIR.'/vendor/symfony/uid',
        ROOT_DIR.'/vendor/web-token/jwt-framework/src',
    ],

    'file_list' => [],
];

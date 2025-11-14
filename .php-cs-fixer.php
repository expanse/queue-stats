<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:3.8.0|configurator
 * you can change this configuration by importing this file.
 */
$config = new Config();

return $config
    ->setRules([
        'align_multiline_comment' => ['comment_type' => 'all_multiline'],
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => true,
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'continue',
                'declare',
                'return',
                'throw',
                'try',
            ],
        ],
        'blank_lines_before_namespace' => [
            'max_line_breaks' => 2,
            'min_line_breaks' => 2,
        ],
        'cast_spaces' => ['space' => 'none'],
        'class_attributes_separation' => [
            'elements' => [
                'case' => 'none',
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'only_if_meta',
            ],
        ],
        'compact_nullable_type_declaration' => true,
        'concat_space' => ['spacing' => 'one'],
        'constant_case' => ['case' => 'lower'],
        'control_structure_braces' => true,
        'control_structure_continuation_position' => [
            'position' => 'same_line',
        ],
        'declare_equal_normalize' => ['space' => 'single'],
        'encoding' => true,
        'explicit_string_variable' => true,
        'full_opening_tag' => true,
        'fully_qualified_strict_types' => [
            'import_symbols' => true,
            'leading_backslash_in_global_namespace' => true,
        ],
        'function_declaration' => ['closure_function_spacing' => 'one'],
        'global_namespace_import' => ['import_classes' => true],
        'line_ending' => true,
        'list_syntax' => ['syntax' => 'short'],
        'lowercase_cast' => true,
        'lowercase_keywords' => true,
        'lowercase_static_reference' => true,
        'method_argument_space' => [
            'keep_multiple_spaces_after_comma' => true,
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'method_chaining_indentation' => true,
        'multiline_comment_opening_closing' => true,
        'native_function_casing' => true,
        'native_function_type_declaration_casing' => true,
        'new_with_braces' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_break_comment' => ['comment_text' => 'Fallthru expected'],
        'no_closing_tag' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'use',
                'continue',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'throw',
                'switch',
                'case',
                'default',
            ],
        ],
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_mixed_echo_print' => ['use' => 'echo'],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_short_bool_cast' => true,
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'no_trailing_comma_in_singleline' => true,
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_whitespace_before_comma_in_array' => true,
        'not_operator_with_successor_space' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'operator_linebreak' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'ordered_types' => [
            'case_sensitive' => false,
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'alpha',
        ],
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        'phpdoc_order_by_value' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'return_type_declaration' => ['space_before' => 'one'],
        'short_scalar_cast' => true,
        'single_line_after_imports' => true,
        'single_line_comment_spacing' => true,
        'single_quote' => [
            'strings_containing_single_quote_chars' => true,
        ],
        'statement_indentation' => true,
        'trailing_comma_in_multiline' => true,
        'type_declaration_spaces' => [
            'elements' => ['function', 'property'],
        ],
    ])
    ->setUsingCache(false)
    ->setFinder(
        Finder::create()
            ->exclude(['node_modules', 'resources', 'storage', 'vendor'])
            ->in(__DIR__),
    );

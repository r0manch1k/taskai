<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['var', 'vendor', 'bin']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_extra_blank_lines' => true,
        'line_ending' => true,
        'single_quote' => true,
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
        'declare_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_functions' => true,
            'import_constants' => true,
        ],
        'concat_space' => ['spacing' => 'one'],
    ])
    ->setCacheFile(__DIR__.'/var/cache/.php-cs-fixer.cache')
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setFinder($finder)
;

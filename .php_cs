<?php

return PhpCsFixer\Config::create()
    ->setUsingCache(false)
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'heredoc_to_nowdoc' => true,
        'increment_style' => ['style' => 'post'],
        'no_useless_else' => true,
        'no_useless_return' => true,
        'not_operator_with_successor_space' => true,
        'ordered_imports' => ['sortAlgorithm' => 'length'],
        'yoda_style' => false,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude([
                'bootstrap/cache',
                'node_modules',
                'storage',
                'vendor',
            ])
            ->in(__DIR__)
    );
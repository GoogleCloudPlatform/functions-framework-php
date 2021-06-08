<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2' => true,
        '@PHP71Migration' => true,
        'concat_space' => ['spacing' => 'one'],
        'no_unused_imports' => true,
        'method_argument_space' => false,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
            ->exclude(__DIR__.'/vendor')
    )
;

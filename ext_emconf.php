<?php

/** @noinspection PhpMissingStrictTypesDeclarationInspection */

/** @var string $_EXTKEY */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Cache optimizer',
    'description' => 'Optimizes automatic cache clearing.',
    'category' => 'be',
    'version' => '12.0.0',
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearcacheonload' => true,
    'author' => 'Alexander Stehlik',
    'author_email' => 'alexander.stehlik.deleteme@gmail.com',
    'author_company' => 'Intera GmbH',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.3.99',
            'typo3' => '12.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];

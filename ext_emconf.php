<?php

/** @noinspection PhpMissingStrictTypesDeclarationInspection */

/** @var string $_EXTKEY */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Cache optimizer',
    'description' => 'Optimizes automatic cache clearing.',
    'category' => 'be',
    'version' => '13.0.0',
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearcacheonload' => true,
    'author' => 'Alexander Stehlik',
    'author_email' => 'alexander.stehlik.deleteme@gmail.com',
    'author_company' => 'Intera GmbH',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.3.99',
            'typo3' => '13.2.0-13.2.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];

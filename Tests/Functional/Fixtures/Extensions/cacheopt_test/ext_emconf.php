<?php

/** @noinspection PhpMissingStrictTypesDeclarationInspection */

/** @var string $_EXTKEY */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Cache optimizer test',
    'description' => 'Test records for the cacheopt Extension.',
    'category' => 'fe',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Alexander Stehlik',
    'author_email' => 'astehlik.deleteme@intera.de',
    'author_company' => 'Intera GmbH',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => ['typo3' => '11.5.0-11.5.99'],
        'conflicts' => [],
        'suggests' => [],
    ],
];

<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'title' => 'Cacheopt record',
        'delete' => 'deleted',
        'searchFields' => 'title',
    ],
    'interface' => ['always_description' => 0],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'eval' => 'required',
                'size' => '50',
                'max' => '256',
            ],
        ],
    ],
    'types' => ['0' => ['showitem' => 'title']],
    'palettes' => [],
];

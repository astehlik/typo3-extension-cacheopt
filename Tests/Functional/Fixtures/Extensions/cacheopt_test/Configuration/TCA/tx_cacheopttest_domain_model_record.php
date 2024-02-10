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
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'required' => true,
                'size' => '50',
                'max' => '256',
            ],
        ],
    ],
    'types' => ['0' => ['showitem' => 'title']],
    'palettes' => [],
];

<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

ExtensionUtility::registerPlugin(
    'CacheoptTest',
    'RecordRenderPlugin',
    'Cacheopt - Record renderer plugin'
);

ExtensionUtility::registerPlugin(
    'CacheoptTest',
    'RecordRenderContent',
    'Cacheopt - Record renderer content'
);

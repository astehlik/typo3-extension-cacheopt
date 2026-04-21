<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || exit;

// Silence deprecation warnings about list_type content elements because we still
// want to support them as long as they exist.
// TODO: remove when upgrading to TYPO3 14
@ExtensionUtility::registerPlugin(
    'CacheoptTest',
    'RecordRenderPlugin',
    'Cacheopt - Record renderer plugin',
);

ExtensionUtility::registerPlugin(
    'CacheoptTest',
    'RecordRenderContent',
    'Cacheopt - Record renderer content',
);

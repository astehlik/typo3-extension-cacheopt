<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

/** @noinspection PhpMissingStrictTypesDeclarationInspection */

defined('TYPO3') or die();

/** @uses \Tx\CacheoptTest\Controller\RecordController::displayAction() */
TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'CacheoptTest',
    'RecordRenderPlugin',
    [\Tx\CacheoptTest\Controller\RecordController::class => 'display']
);

/** @uses \Tx\CacheoptTest\Controller\RecordController::displayAction() */
TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'CacheoptTest',
    'RecordRenderContent',
    [\Tx\CacheoptTest\Controller\RecordController::class => 'display'],
    [],
    TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

Tx\Cacheopt\CacheOptimizerRegistry::getInstance()->registerContentForTable(
    'tx_cacheopttest_domain_model_record',
    'cacheopttest_recordrendercontent'
);
Tx\Cacheopt\CacheOptimizerRegistry::getInstance()->registerPluginForTable(
    'tx_cacheopttest_domain_model_record',
    'cacheopttest_recordrenderplugin'
);
Tx\Cacheopt\CacheOptimizerRegistry::getInstance()->registerContentForTable(
    'pages',
    'cacheopttest_filteredpagecontent',
    static fn(array $record): bool => (int)$record['doktype'] === 199
);

/** @uses \Tx\CacheoptTest\Controller\CacheApiUsageController::registerRecordAction() */
TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'CacheoptTest',
    'CacheApiRegisterRecord',
    [\Tx\CacheoptTest\Controller\CacheApiUsageController::class => 'registerRecord'],
    [],
    TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

/** @noinspection PhpMissingStrictTypesDeclarationInspection */

defined('TYPO3') or die();

// Hook into the data handler to clear the cache for related records.
// Make sure we are the first processor so that other processors handle the pages we added.
/** @uses \Tx\Cacheopt\CacheOptimizerDataHandler::dataHandlerClearPageCacheEval() */
if (
    isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'])
    && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'])
) {
    array_unshift(
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'],
        Tx\Cacheopt\CacheOptimizerDataHandler::class . '->dataHandlerClearPageCacheEval'
    );
} else {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'][] =
        Tx\Cacheopt\CacheOptimizerDataHandler::class . '->dataHandlerClearPageCacheEval';
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit']['tx_cacheopt']
    = Tx\Cacheopt\TagCollector\ContentTagCollector::class;

// Discover records hidden by a future starttime, referenced via shortcut or CONTENT cObject.
// Can be disabled via the extension configuration (enableStarttimeDiscovery), since this
// relies on Xclass overrides of TYPO3 core classes.
$extensionConfiguration = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
);
if ((bool)$extensionConfiguration->get('cacheopt', 'enableStarttimeDiscovery')) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Core\Database\RelationHandler::class] = [
        'className' => Tx\Cacheopt\Xclass\RelationHandler::class,
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class] = [
        'className' => Tx\Cacheopt\Xclass\ContentObjectRenderer::class,
    ];
}
unset($extensionConfiguration);

$cacheOptimizerRegistry = Tx\Cacheopt\CacheOptimizerRegistry::getInstance();

// Default configuration for the cz_simple_cal Extension.
if (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cz_simple_cal')) {
    $cacheOptimizerRegistry->registerPluginForTables(
        [
            'tx_czsimplecal_domain_model_address',
            'tx_czsimplecal_domain_model_category',
            'tx_czsimplecal_domain_model_event',
        ],
        'czsimplecal_pi1'
    );
}

unset($cacheOptimizerRegistry);

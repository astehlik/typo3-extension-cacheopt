<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

/** @noinspection PhpMissingStrictTypesDeclarationInspection */

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

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

if (TYPO3_MODE === 'FE' || defined('TX_CACHEOPT_FUNCTIONAL_TEST')) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit']['tx_cacheopt']
        = Tx\Cacheopt\TagCollector\ContentTagCollector::class;
}

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

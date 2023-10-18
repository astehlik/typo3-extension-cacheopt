<?php

declare(strict_types=1);

namespace Tx\Cacheopt;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "cacheopt".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * API methods that can be used by extensions.
 */
class CacheApi implements SingletonInterface
{
    protected ?CacheManager $cacheManager = null;

    /**
     * Flushes the cache for the given page.
     *
     * @param bool $useDataHandler If this is true the DataHandler will be used
     *                             instead of the CacheManager for cache clearing. This makes sure that the
     *                             hooks registered for clearPageCacheEval are called (e.g. those of realurl).
     *
     * @throws NoSuchCacheGroupException
     * @throws \InvalidArgumentException
     */
    public function flushCacheForPage(int $pageId, bool $useDataHandler): void
    {
        if ($useDataHandler) {
            $this->flushCacheForRecordWithDataHandler('pages', $pageId);
            return;
        }

        $this->initializeCacheManager();
        $this->cacheManager->flushCachesInGroupByTag('pages', 'pageId_' . $pageId);
    }

    /**
     * Initializes an instance of the DataHandler, registers the given record for
     * cache clearing and starts the cache clearing process of the DataHandler.
     *
     * This process makes sure that the hooks registered for clearPageCacheEval
     * are called (e.g. those of cacheopt or those of realurl).
     *
     * @throws \InvalidArgumentException
     */
    public function flushCacheForRecordWithDataHandler(string $tablename, int $uid): void
    {
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->start([], []);

        $tce->registerRecordIdForPageCacheClearing($tablename, $uid);
        $tce->process_datamap();
    }

    /**
     * Loads an instance of the cache manager in the cacheManager class variable.
     *
     * @throws \InvalidArgumentException
     */
    protected function initializeCacheManager(): void
    {
        if ($this->cacheManager === null) {
            $this->cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        }
    }
}

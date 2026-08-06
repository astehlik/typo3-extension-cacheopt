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

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator;

/**
 * API methods that can be used by extensions.
 */
class CacheApi implements SingletonInterface
{
    protected ?CacheManager $cacheManager = null;

    public function __construct(
        private readonly CacheLifetimeCalculator $cacheLifetimeCalculator,
    ) {}

    /**
     * Flushes the cache for the given page.
     *
     * @param bool $useDataHandler If this is true the DataHandler will be used
     *                             instead of the CacheManager for cache clearing. This makes sure that the
     *                             hooks registered for clearPageCacheEval are called (e.g. those of realurl).
     *
     * @throws NoSuchCacheGroupException
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
     */
    public function flushCacheForRecordWithDataHandler(string $tablename, int $uid): void
    {
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->start([], []);

        $tce->registerRecordIdForPageCacheClearing($tablename, $uid);
        $tce->process_datamap();
    }

    /**
     * Registers the record's starttime/endtime (if any) and caps the page cache lifetime
     * accordingly, and tags the page cache with the record so it is flushed when the
     * record changes.
     *
     * Use this to make a rendered record's cache tags and cache lifetime known to the page
     * cache when it is rendered outside of the regular ContentObjectRenderer flow (e.g. by
     * a custom plugin or ContentObject that does not call ContentObjectRenderer::start()),
     * which TYPO3 core cannot take into account automatically in that case.
     */
    public function registerRecord(string $table, array $record, ServerRequestInterface $request): void
    {
        $this->registerRecordCacheTags($table, $record, $request);
        $this->registerRecordCacheLifetime($table, $record, $request);
    }

    /**
     * Caps the page cache lifetime according to the record's starttime/endtime, using
     * TYPO3 core's CacheLifetimeCalculator.
     */
    public function registerRecordCacheLifetime(string $table, array $record, ServerRequestInterface $request): void
    {
        $cacheCollector = $this->getFrontendCacheCollector($request);
        if ($cacheCollector === null) {
            return;
        }

        $cacheCollector->restrictMaximumLifetime(
            $this->cacheLifetimeCalculator->calculateLifetimeForRow($table, $record)
        );
    }

    /**
     * Tags the page cache with the given record, using the cache data collector found on
     * the given request, so that the page cache is flushed when the record changes.
     */
    public function registerRecordCacheTags(string $table, array $record, ServerRequestInterface $request): void
    {
        $cacheCollector = $this->getFrontendCacheCollector($request);
        if ($cacheCollector === null) {
            return;
        }

        $uid = (int)($record['uid'] ?? 0);
        if ($table === '' || $uid === 0) {
            return;
        }

        $cacheTags = [new CacheTag($table . '_' . $uid)];

        if (array_key_exists('_LOCALIZED_UID', $record) && (int)$record['_LOCALIZED_UID'] !== 0) {
            $cacheTags[] = new CacheTag($table . '_' . $record['_LOCALIZED_UID']);
        }

        // @extensionScannerIgnoreLine - False positive, this is CacheDataCollector::addCacheTags(),
        // not the removed TypoScriptFrontendController one.
        $cacheCollector->addCacheTags(...$cacheTags);
    }

    /**
     * Loads an instance of the cache manager in the cacheManager class variable.
     *
     * @throws InvalidArgumentException
     */
    protected function initializeCacheManager(): void
    {
        if ($this->cacheManager === null) {
            $this->cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        }
    }

    private function getFrontendCacheCollector(ServerRequestInterface $request): ?CacheDataCollector
    {
        $cacheCollector = $request->getAttribute('frontend.cache.collector');

        return $cacheCollector instanceof CacheDataCollector ? $cacheCollector : null;
    }
}

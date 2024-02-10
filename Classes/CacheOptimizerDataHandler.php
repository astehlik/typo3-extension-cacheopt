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

use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This cache optimizer hooks into the data handler to determine additional
 * pages for which the cache should be cleared.
 */
class CacheOptimizerDataHandler
{
    protected CacheOptimizerRegistry $cacheOptimizerRegistry;

    /**
     * The array of page UIDs for which the cache should be flushed in the current DataHandler run.
     */
    protected array $currentPageIdArray;

    /**
     * Is called by the data handler within the processClearCacheQueue() method and
     * adds related records to the cache clearing queue.
     *
     * @param array $parameters Parameters array containing:
     *                          pageIdArray => reference to indexed array containing the records for which the cache
     *                          should be cleared
     *                          table => the name of the table of the current record
     *                          uid =>  the uid of the record
     *                          functionID => is always clear_cache()
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function dataHandlerClearPageCacheEval(
        array $parameters,
        /** @noinspection PhpUnusedParameterInspection */
        DataHandler $dataHandler
    ): void {
        $this->initialize();

        if ($parameters['functionID'] !== 'clear_cache()') {
            return;
        }

        $this->cacheOptimizerRegistry->registerPagesWithFlushedCache($parameters['pageIdArray']);

        $table = $parameters['table'];
        $uid = (int)$parameters['uid'];

        if ($this->cacheOptimizerRegistry->isProcessedRecord($table, $uid)) {
            return;
        }

        $this->cacheOptimizerRegistry->registerProcessedRecord($table, $uid);

        $this->currentPageIdArray = &$parameters['pageIdArray'];
        $this->registerRelatedPluginPagesForCacheFlush($table);
    }

    /**
     * Returns a where statement that excludes all page UIDs (pid field)
     * for which the cache is already flushed.
     *
     * @param bool $neverExcludeRoot if TRUE the TYPO3 root (pid = 0) will never be excluded
     */
    protected function getPidExcludeStatement(bool $neverExcludeRoot, QueryBuilder $queryBuilder): void
    {
        $flushedCachePids = $this->cacheOptimizerRegistry->getFlushedCachePageUids();
        if (count($flushedCachePids) === 0) {
            return;
        }

        $pidQuery = $queryBuilder->expr()->notIn(
            'pid',
            $queryBuilder->createNamedParameter($flushedCachePids, Connection::PARAM_INT_ARRAY)
        );

        if ($neverExcludeRoot) {
            $pidQuery = $queryBuilder->expr()->or(
                $pidQuery,
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            );
        }

        $queryBuilder->andWhere($pidQuery);
    }

    /**
     * Builds a where statement that selects all tt_content elements that
     * have a content type or a plugin type that is related to the given table.
     */
    protected function getTtContentWhereStatementForTable(string $table, QueryBuilder $queryBuilder): void
    {
        $this->initialize();
        $orStatements = [];

        $contentTypesForTable = $this->cacheOptimizerRegistry->getContentTypesForTable($table);
        if ($contentTypesForTable !== []) {
            $orStatements[] = $queryBuilder->expr()->in(
                'tt_content.CType',
                $queryBuilder->createNamedParameter($contentTypesForTable, Connection::PARAM_STR_ARRAY)
            );
        }

        $pluginTypesForTable = $this->cacheOptimizerRegistry->getPluginTypesForTable($table);
        if ($pluginTypesForTable !== []) {
            $orStatements[] = $queryBuilder->expr()->and(
                $queryBuilder->expr()->eq('tt_content.CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->in(
                    'tt_content.list_type',
                    $queryBuilder->createNamedParameter($pluginTypesForTable, Connection::PARAM_STR_ARRAY)
                )
            );
        }

        if (count($orStatements) === 0) {
            return;
        }

        if (count($orStatements) === 1) {
            $queryBuilder->andWhere($orStatements[0]);
            return;
        }

        $queryBuilder->andWhere($queryBuilder->expr()->or(...$orStatements));
    }

    /**
     * Initializes required objects.
     *
     * @throws \InvalidArgumentException
     */
    protected function initialize(): void
    {
        $this->cacheOptimizerRegistry = CacheOptimizerRegistry::getInstance();
    }

    /**
     * Checks if the cache for the given page was already flushed in the current
     * run and if not flushCacheForPage() will be called in the parent class.
     */
    protected function registerPageForCacheFlush(int $pid): void
    {
        if ($this->cacheOptimizerRegistry->pageCacheIsFlushed($pid)) {
            return;
        }
        $this->cacheOptimizerRegistry->registerPageWithFlushedCache($pid);
        $this->currentPageIdArray[] = $pid;
    }

    /**
     * Registers all pages for cache flush that contain contents related to records of the given table.
     * Internal use, should be called by flushRelatedCacheForRecord() only!
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function registerRelatedPluginPagesForCacheFlush(string $table): void
    {
        if (!$this->cacheOptimizerRegistry->isRegisteredPluginTable($table)) {
            return;
        }

        $queryBuilder = $this->getQueryBuilderForTtContent();
        $queryBuilder->select('pid')
            ->from('tt_content')
            ->groupBy('pid');

        $this->getPidExcludeStatement(false, $queryBuilder);
        $this->getTtContentWhereStatementForTable($table, $queryBuilder);

        $pageUidResult = $queryBuilder->executeQuery();

        while ($pageUid = (int)$pageUidResult->fetchOne()) {
            $this->registerPageForCacheFlush($pageUid);
        }
    }

    private function getQueryBuilderForTtContent(): QueryBuilder
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        return $connectionPool->getQueryBuilderForTable('tt_content');
    }
}

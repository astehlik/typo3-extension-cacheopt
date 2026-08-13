<?php

declare(strict_types=1);

namespace Tx\Cacheopt\Xclass;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "cacheopt".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 */

use Doctrine\DBAL\Result;
use Tx\Cacheopt\Cache\StarttimeDiscoveryState;
use Tx\Cacheopt\CacheApi;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer as CoreContentObjectRenderer;

/**
 * CONTENT/DatabaseQueryProcessor fetch records via exec_getQuery(), which
 * bakes the starttime restriction into the query before it runs, so a
 * record hidden by a future starttime is never selected or tagged. This
 * runs the same query again with starttime ignored (via
 * StarttimeDiscoveryState + SuppressStarttimeConstraintEventListener) and
 * an explicit "starttime in the future" condition, to cap the page cache
 * lifetime without letting hidden content reach the real render output.
 *
 * endtime is not handled here - visible records already get tagged by
 * core's own auto-tagging in getRecords().
 */
class ContentObjectRenderer extends CoreContentObjectRenderer
{
    public function exec_getQuery($table, $conf): Result
    {
        $result = parent::exec_getQuery($table, $conf);
        $this->registerLifetimeForUpcomingRecords($table, $conf);
        return $result;
    }

    private function registerLifetimeForUpcomingRecords(string $table, array $conf): void
    {
        $request = $this->getRequest();

        // Avoid the discovery query below entirely outside a real frontend request (e.g.
        // backend preview rendering, CLI), where CacheApi would end up being a no-op anyway.
        if (!$request->getAttribute('frontend.cache.collector') instanceof CacheDataCollector) {
            return;
        }

        $tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        if (!$tcaSchemaFactory->has($table)) {
            return;
        }

        $schema = $tcaSchemaFactory->get($table);
        if (!$schema->hasCapability(TcaSchemaCapability::RestrictionStartTime)) {
            return;
        }

        $starttimeColumn = $schema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName();

        // Force SELECT * so starttime is present regardless of a narrowed selectFields conf.
        unset($conf['selectFields'], $conf['selectFields.']);

        // Add "starttime in the future" to the where clause. Resolve where.'s stdWrap
        // ourselves first (getQuery() would otherwise do it, but on our replacement value),
        // then drop where. so getQuery() doesn't process it again.
        $starttimeCondition = $table . '.' . $starttimeColumn . ' > ' . (int)$GLOBALS['EXEC_TIME'];
        $existingWhere = isset($conf['where.'])
            ? trim((string)$this->stdWrap($conf['where'] ?? '', $conf['where.']))
            : trim((string)($conf['where'] ?? ''));
        $conf['where'] = $existingWhere !== ''
            ? '(' . $existingWhere . ') AND ' . $starttimeCondition
            : $starttimeCondition;
        unset($conf['where.']);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $state = GeneralUtility::makeInstance(StarttimeDiscoveryState::class);
        $state->activate();

        try {
            $statement = $this->getQuery($connection, $table, $conf);
        } finally {
            $state->deactivate();
        }

        if ($statement === '') {
            return;
        }

        $cacheApi = GeneralUtility::makeInstance(CacheApi::class);
        $result = $connection->executeQuery($statement);
        while ($row = $result->fetchAssociative()) {
            $cacheApi->registerRecord($table, $row, $request);
        }
    }
}

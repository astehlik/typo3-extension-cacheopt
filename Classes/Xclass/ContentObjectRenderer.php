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

use Psr\Http\Message\ServerRequestInterface;
use Tx\Cacheopt\CacheApi;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer as CoreContentObjectRenderer;

/**
 * CONTENT/DatabaseQueryProcessor fetch records via exec_getQuery(), which
 * bakes the TCA enablecolumns restriction into the query before it runs, so
 * a record hidden by a future starttime is never selected or tagged. This
 * runs the same query again with starttime ignored and an explicit
 * "starttime in the future" condition, to cap the page cache lifetime
 * without letting hidden content reach the real render output.
 *
 * endtime is not handled here - visible records already render normally
 * and get tagged by ContentTagCollector.
 */
class ContentObjectRenderer extends CoreContentObjectRenderer
{
    public function exec_getQuery($table, $conf)
    {
        $result = parent::exec_getQuery($table, $conf);
        $this->registerLifetimeForUpcomingRecords($table, $conf);
        return $result;
    }

    private function registerLifetimeForUpcomingRecords(string $table, array $conf): void
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        // Avoid the discovery query below entirely outside a real frontend request (e.g.
        // backend preview rendering, CLI), where the result would never be used anyway.
        if (
            !$request instanceof ServerRequestInterface
            || !ApplicationType::fromRequest($request)->isFrontend()
        ) {
            return;
        }

        if (!isset($GLOBALS['TCA'][$table])) {
            return;
        }

        $enableColumns = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'] ?? [];
        $starttimeColumn = $enableColumns['starttime'] ?? null;
        if (!is_string($starttimeColumn) || $starttimeColumn === '') {
            return;
        }

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

        $originalEnableColumns = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'];
        unset($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime']);

        try {
            $statement = $this->getQuery($table, $conf);
        } finally {
            $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'] = $originalEnableColumns;
        }

        if ($statement === '') {
            return;
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $result = $connection->executeQuery($statement);

        $cacheApi = GeneralUtility::makeInstance(CacheApi::class);
        while ($row = $result->fetchAssociative()) {
            $cacheApi->registerRecordCacheLifetime($table, $row);
        }
    }
}

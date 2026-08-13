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
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\RelationHandler as CoreRelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * RECORDS/shortcut elements load referenced records via getFromDB(), which
 * filters out records hidden by a future starttime before they ever reach
 * core's own auto-tagging. This runs an extra unrestricted starttime lookup
 * on $tableArray's candidates (populated before filtering) and registers
 * them via CacheApi, so the page cache lifetime is still capped.
 *
 * endtime is not handled here - visible records already render normally
 * and get tagged by core's own auto-tagging in ContentObjectRenderer.
 *
 * Limitation: if a custom source.postUserFunc (or similar) rewrites the
 * source string before RelationHandler::start() parses it - e.g. applying
 * its own visibility filtering - a hidden record removed at that stage
 * never reaches $tableArray and cannot be discovered here.
 */
class RelationHandler extends CoreRelationHandler
{
    public function getFromDB(): array
    {
        $results = parent::getFromDB();
        $this->registerLifetimeForReferencedRecords($this->tableArray);
        return $results;
    }

    private function registerLifetimeForReferencedRecords(array $tableArray): void
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            return;
        }

        // Avoid the discovery queries below entirely outside a real frontend request (e.g.
        // backend preview rendering, CLI), where CacheApi would end up being a no-op anyway.
        if (!$request->getAttribute('frontend.cache.collector') instanceof CacheDataCollector) {
            return;
        }

        $cacheApi = GeneralUtility::makeInstance(CacheApi::class);

        foreach ($tableArray as $table => $ids) {
            if (!is_array($ids) || $ids === [] || !isset($GLOBALS['TCA'][$table])) {
                continue;
            }

            $enableColumns = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'] ?? [];
            $starttimeColumn = $enableColumns['starttime'] ?? null;
            if (!is_string($starttimeColumn) || $starttimeColumn === '') {
                continue;
            }

            $connection = $this->getConnectionForTableName($table);
            $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());
            $uids = array_unique(array_map('intval', $ids));

            foreach (array_chunk($uids, $maxBindParameters - 10) as $chunk) {
                $queryBuilder = $connection->createQueryBuilder();
                $queryBuilder->getRestrictions()->removeAll();
                $statement = $queryBuilder
                    ->select('uid', $starttimeColumn)
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->in(
                            'uid',
                            $queryBuilder->createNamedParameter($chunk, Connection::PARAM_INT_ARRAY),
                        ),
                        $queryBuilder->expr()->gt(
                            $starttimeColumn,
                            $queryBuilder->createNamedParameter((int)$GLOBALS['EXEC_TIME'], Connection::PARAM_INT),
                        ),
                    )
                    ->executeQuery();

                while ($row = $statement->fetchAssociative()) {
                    $cacheApi->registerRecord($table, $row, $request);
                }
            }
        }
    }
}

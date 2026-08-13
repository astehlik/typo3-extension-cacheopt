<?php

declare(strict_types=1);

namespace Tx\Cacheopt\Xclass;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "cacheopt".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 */

use Tx\Cacheopt\Cache\ContentLifetimeRegistry;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\RelationHandler as CoreRelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * RECORDS/shortcut elements load referenced records via getFromDB(), which
 * filters out records hidden by a future starttime before they ever reach
 * ContentTagCollector. This runs an extra unrestricted starttime lookup on
 * $tableArray's candidates (populated before filtering) to cap the page
 * cache lifetime anyway.
 *
 * endtime is not handled here - visible records already render normally
 * and get tagged by ContentTagCollector.
 */
class RelationHandler extends CoreRelationHandler
{
    public function getFromDB()
    {
        $results = parent::getFromDB();
        $this->registerLifetimeForReferencedRecords($this->tableArray);
        return $results;
    }

    private function registerLifetimeForReferencedRecords(array $tableArray): void
    {
        $contentLifetimeRegistry = GeneralUtility::makeInstance(ContentLifetimeRegistry::class);

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
                            $queryBuilder->createNamedParameter($chunk, Connection::PARAM_INT_ARRAY)
                        ),
                        $queryBuilder->expr()->gt(
                            $starttimeColumn,
                            $queryBuilder->createNamedParameter((int)$GLOBALS['EXEC_TIME'], Connection::PARAM_INT)
                        )
                    )
                    ->executeQuery();

                while ($row = $statement->fetchAssociative()) {
                    $contentLifetimeRegistry->registerLifetimeRestriction($table, $row);
                }
            }
        }
    }
}

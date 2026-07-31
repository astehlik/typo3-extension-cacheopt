<?php

declare(strict_types=1);

namespace Tx\Cacheopt\Cache;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "cacheopt".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Collects the starttime/endtime of every record rendered on the current
 * page, regardless of the page it is stored on, so that the page cache
 * lifetime can be capped accordingly.
 *
 * TYPO3 core only takes starttime/endtime into account for records that
 * reside directly on the page being cached (see CacheLifetimeCalculator).
 */
class ContentLifetimeRegistry implements SingletonInterface
{
    /**
     * The earliest upcoming starttime/endtime timestamp found so far, or
     * PHP_INT_MAX if none was registered yet.
     */
    protected int $minimumTimestamp = PHP_INT_MAX;

    /**
     * Returns the earliest upcoming starttime/endtime timestamp registered
     * so far, or PHP_INT_MAX if none was registered.
     */
    public function getMinimumTimestamp(): int
    {
        return $this->minimumTimestamp;
    }

    /**
     * Registers the record's starttime/endtime (if any) with the ContentLifetimeRegistry,
     * capping the page cache lifetime even if the record is rendered from a different pid
     * than the page (e.g. via a RECORDS content element or a shortcut), which TYPO3 core's
     * own cache lifetime calculation does not take into account in that case.
     */
    public function registerLifetimeRestriction(string $table, array $record): void
    {
        $enableColumns = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'] ?? [];

        foreach (['starttime', 'endtime'] as $field) {
            $columnName = $enableColumns[$field] ?? null;
            if (!is_string($columnName) || $columnName === '' || !array_key_exists($columnName, $record)) {
                continue;
            }

            $this->registerTimestamp((int)$record[$columnName]);
        }
    }

    /**
     * Registers a starttime/endtime timestamp that should limit the page
     * cache lifetime. Timestamps that are not in the future are ignored,
     * as they no longer restrict future cache validity.
     */
    public function registerTimestamp(int $timestamp): void
    {
        if ($timestamp <= (int)$GLOBALS['EXEC_TIME']) {
            return;
        }

        $this->minimumTimestamp = min($this->minimumTimestamp, $timestamp);
    }
}

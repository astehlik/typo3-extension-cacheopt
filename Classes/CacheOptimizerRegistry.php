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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Central registry that contains information about which tables are connected
 * to which content types.
 *
 * It also stores information about all records / pages / folders for which the
 * cache has already been flushed to prevent duplicate cache flushing.
 */
class CacheOptimizerRegistry implements SingletonInterface
{
    /**
     * The content types registered for each table.
     *
     * @var array<string, list<TypeRegistration>>
     */
    protected array $contentTypesByTable = [];

    /**
     * Array containing UIDs of pages for which the cache has been flushed already.
     */
    protected array $flushedPageUids = [];

    /**
     * The plugin types registered for each table.
     *
     * @var array<string, list<TypeRegistration>>
     */
    protected array $pluginTypesByTable = [];

    /**
     * Array containing the identifiers of the folders for which the cache has already been flushed.
     * array(
     *   'storageUid' => array('directoryIdentifier' => 1)
     * ).
     */
    protected array $processedFolders = [];

    /**
     * Array containing the records that already have been processed:
     * array(
     *   'tablename' => array('recordUid' => 1)
     * ).
     */
    protected array $processedRecords = [];

    /**
     * Returns an instance of the CacheOptimizerRegistry.
     *
     * @throws \InvalidArgumentException
     */
    public static function getInstance(): self
    {
        return GeneralUtility::makeInstance('Tx\Cacheopt\CacheOptimizerRegistry');
    }

    /**
     * Returns an array containing the content types that belong to the given
     * record: all types registered without a filter and those whose filter
     * accepts the record. A NULL record (e.g. one that could not be loaded)
     * only matches types registered without a filter.
     *
     * @return string[]
     */
    public function getContentTypesForRecord(string $table, ?array $record): array
    {
        return $this->getTypesForRecord($this->contentTypesByTable[$table] ?? [], $record);
    }

    /**
     * Returns an array containing all content types that belong to the given
     * table, regardless of any registered record filters.
     *
     * @return string[]
     */
    public function getContentTypesForTable(string $table): array
    {
        return array_column($this->contentTypesByTable[$table] ?? [], 'type');
    }

    /**
     * Returns an array containing all page UIDs for which the cache was flushed already.
     */
    public function getFlushedCachePageUids(): array
    {
        return array_unique($this->flushedPageUids);
    }

    /**
     * Returns an array containing the plugin types that belong to the given
     * record, see getContentTypesForRecord().
     *
     * @return string[]
     */
    public function getPluginTypesForRecord(string $table, ?array $record): array
    {
        return $this->getTypesForRecord($this->pluginTypesByTable[$table] ?? [], $record);
    }

    /**
     * Returns an array containing all plugin types that belong to the given
     * table, regardless of any registered record filters.
     *
     * @return string[]
     */
    public function getPluginTypesForTable(string $table): array
    {
        return array_column($this->pluginTypesByTable[$table] ?? [], 'type');
    }

    /**
     * Returns TRUE if at least one content or plugin type of the given table
     * was registered with a record filter.
     */
    public function hasRecordFilterForTable(string $table): bool
    {
        $registrations = array_merge(
            $this->contentTypesByTable[$table] ?? [],
            $this->pluginTypesByTable[$table] ?? []
        );

        foreach ($registrations as $registration) {
            if ($registration->hasRecordFilter()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns TRUE if the given folder in the given storage was already processed.
     */
    public function isProcessedFolder(int $storageUid, string $folderIdentifier): bool
    {
        return isset($this->processedFolders[$storageUid][$folderIdentifier]);
    }

    /**
     * Return TRUE if the record with the given UID in the given table was already processed.
     */
    public function isProcessedRecord(string $table, int $uid): bool
    {
        return isset($this->processedRecords[$table][$uid]);
    }

    public function isRegisteredPluginTable(string $table): bool
    {
        if ($this->getContentTypesForTable($table) !== []) {
            return true;
        }

        return $this->getPluginTypesForTable($table) !== [];
    }

    /**
     * Returns TRUE if the cache for the page with the given UID was already flushed.
     */
    public function pageCacheIsFlushed(int $pid): bool
    {
        if ($pid === 0) {
            return true;
        }

        return in_array($pid, $this->flushedPageUids, true) !== false;
    }

    /**
     * Let the registry know that the given table is related to the given content type.
     *
     * @param string $table the name of the table
     * @param string $contentType the value in the CType column
     * @param ?\Closure(array $record): bool $recordFilter only flush when the changed record is accepted by this filter
     *
     * @api
     */
    public function registerContentForTable(string $table, string $contentType, ?\Closure $recordFilter = null): void
    {
        $this->contentTypesByTable[$table][] = new TypeRegistration($contentType, $recordFilter);
    }

    /**
     * Let the registry know that the given tables are related to the given content type.
     * All tables are automatically excluded from refindex traversal.
     *
     * @param ?\Closure(array $record): bool $recordFilter
     */
    public function registerContentForTables(array $tables, string $contentType, ?\Closure $recordFilter = null): void
    {
        foreach ($tables as $table) {
            $this->registerContentForTable($table, $contentType, $recordFilter);
        }
    }

    /**
     * Marks all page UIDs contained in the given array as cache flushed.
     */
    public function registerPagesWithFlushedCache(array $pidArray): void
    {
        foreach ($pidArray as $pid) {
            $this->registerPageWithFlushedCache($pid);
        }
    }

    /**
     * The cache for the page with the given ID was flushed.
     */
    public function registerPageWithFlushedCache(int $pid): void
    {
        $this->flushedPageUids[] = $pid;
    }

    /**
     * Let the registry know that the given table is related to the given plugin type.
     *
     * @param string $table the name of the table
     * @param string $listType the value in the list_type column
     * @param ?\Closure(array $record): bool $recordFilter only flush when the changed record is accepted by this filter
     *
     * @api
     */
    public function registerPluginForTable(string $table, string $listType, ?\Closure $recordFilter = null): void
    {
        $this->pluginTypesByTable[$table][] = new TypeRegistration($listType, $recordFilter);
    }

    /**
     * Let the registry know that the given tables are related to the given plugin type.
     * All tables are automatically excluded from refindex traversal.
     *
     * @param ?\Closure(array $record): bool $recordFilter
     *
     * @api
     */
    public function registerPluginForTables(array $tables, string $listType, ?\Closure $recordFilter = null): void
    {
        foreach ($tables as $table) {
            $this->registerPluginForTable($table, $listType, $recordFilter);
        }
    }

    /**
     * The folder in the given storage with the given identifier has been processed.
     */
    public function registerProcessedFolder(int $storageUid, string $folderIdentifier): void
    {
        $this->processedFolders[$storageUid][$folderIdentifier] = true;
    }

    /**
     * The record in the given table with the given uid has been processed.
     */
    public function registerProcessedRecord(string $table, int $uid): void
    {
        $this->processedRecords[$table][$uid] = true;
    }

    /**
     * @param list<TypeRegistration> $registrations
     *
     * @return string[]
     */
    private function getTypesForRecord(array $registrations, ?array $record): array
    {
        $types = [];

        foreach ($registrations as $registration) {
            if ($registration->matchesRecord($record)) {
                $types[] = $registration->type;
            }
        }

        return array_values(array_unique($types));
    }
}

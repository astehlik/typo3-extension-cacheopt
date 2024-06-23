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
     * Array containing information which table is related to which content type:
     * array(
     *   'ty_myext_mytable' => 'myext_contenttype'
     * ).
     */
    protected array $contentTypesByTable = [];

    /**
     * Array containing UIDs of pages for which the cache has been flushed already.
     */
    protected array $flushedPageUids = [];

    /**
     * Array containing information which table is related to which plugin type:
     * array(
     *   'ty_myext_mytable' => 'myext_plugintype'
     * ).
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
        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * Returns an array containing all content types that belong to the given
     * table or NULL if no content types are registered.
     */
    public function getContentTypesForTable(string $table): array
    {
        if (!array_key_exists($table, $this->contentTypesByTable)) {
            return [];
        }

        return $this->contentTypesByTable[$table];
    }

    /**
     * Returns an array containing all page UIDs for which the cache was flushed already.
     */
    public function getFlushedCachePageUids(): array
    {
        return array_unique($this->flushedPageUids);
    }

    /**
     * Returns an array containing all plugin types that belong to the given
     * table or NULL if no plugin types are registered.
     */
    public function getPluginTypesForTable(string $table): array
    {
        if (!array_key_exists($table, $this->pluginTypesByTable)) {
            return [];
        }

        return $this->pluginTypesByTable[$table];
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
     *
     * @api
     */
    public function registerContentForTable(string $table, string $contentType): void
    {
        $this->contentTypesByTable[$table][] = $contentType;
    }

    /**
     * Let the registry know that the given tables are related to the given content type.
     * All tables are automatically excluded from refindex traversal.
     */
    public function registerContentForTables(array $tables, string $contentType): void
    {
        foreach ($tables as $table) {
            $this->registerContentForTable($table, $contentType);
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
     * @param string $listType The value in the list_type column.
     *                         Since this makes sense in most cases TRUE is the default value.
     *
     * @api
     */
    public function registerPluginForTable(string $table, string $listType): void
    {
        $this->pluginTypesByTable[$table][] = $listType;
    }

    /**
     * Let the registry know that the given tables are related to the given plugin type.
     * All tables are automatically excluded from refindex traversal.
     *
     * @api
     */
    public function registerPluginForTables(array $tables, string $listType): void
    {
        foreach ($tables as $table) {
            $this->registerPluginForTable($table, $listType);
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
}

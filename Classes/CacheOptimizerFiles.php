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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileContentsSetEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileCopiedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileCreatedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMovedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileRenamedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This cache optimizer hooks into the ResourceStorage and clears the cache
 * for all pages pointing to a changed file or folder.
 */
class CacheOptimizerFiles implements EventSubscriberInterface
{
    protected CacheManager $cacheManager;

    protected CacheOptimizerRegistry $cacheOptimizerRegistry;

    /**
     * Array containing all page UIDs for which the cache should be cleared.
     */
    protected array $flushCacheTags = [];

    public function __construct(CacheManager $cacheManager, CacheOptimizerRegistry $cacheOptimizerRegistry)
    {
        $this->cacheManager = $cacheManager;
        $this->cacheOptimizerRegistry = $cacheOptimizerRegistry;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            /** @uses handleFileAddPost() */
            AfterFileAddedEvent::class => 'handleFileAddPost',
            /** @uses handleFileSetContentsPost() */
            AfterFileContentsSetEvent::class => 'handleFileSetContentsPost',
            /** @uses handleFileCopyPost() */
            AfterFileCopiedEvent::class => 'handleFileCopyPost',
            /** @uses handleFileCreatePost() */
            AfterFileCreatedEvent::class => 'handleFileCreatePost',
            /** @uses handleFileDeletePost() */
            AfterFileDeletedEvent::class => 'handleFileDeletePost',
            /** @uses handleFileMovePost() */
            AfterFileMovedEvent::class => 'handleFileMovePost',
            /** @uses handleFileRenamePost() */
            AfterFileRenamedEvent::class => 'handleFileRenamePost',
            /** @uses handleFileReplacePost() */
            AfterFileReplacedEvent::class => 'handleFileReplacePost',
        ];
    }

    /**
     * Will be called after a file is added to a directory and flushes
     * all caches related to this directory.
     *
     * @throws \RuntimeException
     * @throws NoSuchCacheGroupException
     * @throws \InvalidArgumentException
     */
    public function handleFileAddPost(AfterFileAddedEvent $event): void
    {
        $targetFolder = $event->getFolder();
        $file = $event->getFile();

        $this->flushCacheForRelatedFolders($targetFolder->getStorage()->getUid(), $targetFolder->getIdentifier());
        if ($file instanceof File) {
            $this->registerFileForCacheFlush($file);
        }
        $this->flushCacheForAllRegisteredTags();
    }

    /**
     * Will be called after a file was copied.
     * The cache for all pages related to the target folder will be flushed.
     *
     * @throws \RuntimeException
     * @throws NoSuchCacheGroupException
     * @throws \InvalidArgumentException
     */
    public function handleFileCopyPost(AfterFileCopiedEvent $event): void
    {
        $targetFolder = $event->getFolder();

        $this->flushCacheForRelatedFolders($targetFolder->getStorage()->getUid(), $targetFolder->getIdentifier());
        $this->flushCacheForAllRegisteredTags();
    }

    /**
     * Will be called after a fil was created.
     * The cache for all pages related to the target folder will be flushed.
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws NoSuchCacheGroupException
     */
    public function handleFileCreatePost(AfterFileCreatedEvent $event): void
    {
        $targetFolder = $event->getFolder();

        $this->flushCacheForRelatedFolders($targetFolder->getStorage()->getUid(), $targetFolder->getIdentifier());
        $this->flushCacheForAllRegisteredTags();
    }

    /**
     * Will be called ater a file was deleted.
     * The cache for all pages related to the containing folder will be flushed.
     *
     * @throws \RuntimeException
     * @throws NoSuchCacheGroupException
     * @throws \InvalidArgumentException
     */
    public function handleFileDeletePost(AfterFileDeletedEvent $event): void
    {
        $file = $event->getFile();

        $fileFolder = $file->getParentFolder();
        $this->flushCacheForRelatedFolders($fileFolder->getStorage()->getUid(), $fileFolder->getIdentifier());
        $this->flushCacheForAllRegisteredTags();
    }

    /**
     * Will be called after a file is moved.
     * The cache for all pages pointing to the source directory, to the target directory
     * or to the moved file will be flushed.
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws NoSuchCacheGroupException
     */
    public function handleFileMovePost(AfterFileMovedEvent $event): void
    {
        $originalFolder = $event->getOriginalFolder();
        $file = $event->getFile();

        $this->flushCacheForRelatedFolders($originalFolder->getStorage()->getUid(), $originalFolder->getIdentifier());
        if ($file instanceof File) {
            $this->registerFileForCacheFlush($file);
        }
        $this->flushCacheForAllRegisteredTags();
    }

    /**
     * Will be called after a file was renamed.
     * Flushes the cache for all pages pointing to the file or its parent directory.
     *
     * @throws NoSuchCacheGroupException
     * @throws \InvalidArgumentException
     */
    public function handleFileRenamePost(AfterFileRenamedEvent $event): void
    {
        $file = $event->getFile();

        if ($file instanceof File) {
            $this->registerFileForCacheFlush($file);
        }
        $this->flushCacheForAllRegisteredTags();
    }

    /**
     * Will be called after a file was renamed.
     * Flushes the cache for all pages pointing to the file or its parent directory.
     *
     * @throws NoSuchCacheGroupException
     * @throws \InvalidArgumentException
     */
    public function handleFileReplacePost(AfterFileReplacedEvent $event): void
    {
        $file = $event->getFile();

        if ($file instanceof File) {
            $this->registerFileForCacheFlush($file);
        }

        $this->flushCacheForAllRegisteredTags();
    }

    /**
     * Will be called after the content was changed in the given file.
     * Flushes the cache for all pages pointing to the file or its parent directory.
     *
     * @throws NoSuchCacheGroupException
     * @throws \InvalidArgumentException
     */
    public function handleFileSetContentsPost(AfterFileContentsSetEvent $event): void
    {
        $file = $event->getFile();

        if ($file instanceof File) {
            $this->registerFileForCacheFlush($file);
        }

        $this->flushCacheForAllRegisteredTags();
    }

    /**
     * Clears the cache for all registered page UIDs.
     *
     * @throws NoSuchCacheGroupException
     */
    protected function flushCacheForAllRegisteredTags(): void
    {
        $flushCacheTags = array_unique($this->flushCacheTags);
        $this->flushCacheTags = [];
        foreach ($flushCacheTags as $cacheTag) {
            $this->cacheManager->flushCachesInGroupByTag('pages', $cacheTag);
        }
    }

    /**
     * Searches for all records pointing to the given folder and flushes
     * the related page caches.
     */
    protected function flushCacheForRelatedFolders(int $storageUid, string $folderIdentifier): void
    {
        if ($this->cacheOptimizerRegistry->isProcessedFolder($storageUid, $folderIdentifier)) {
            return;
        }

        $this->cacheOptimizerRegistry->registerProcessedFolder($storageUid, $folderIdentifier);

        $queryBuilder = $this->getQueryBuilderForTable('sys_file_collection');
        $queryBuilder->select('uid')
            ->from('sys_file_collection')
            ->where($queryBuilder->expr()->eq('deleted', 0))
            ->andWhere('type', 'folder')
            ->andWhere('storage', $queryBuilder->createNamedParameter($storageUid, \PDO::PARAM_INT))
            ->andWhere('folder', $queryBuilder->createNamedParameter($folderIdentifier));

        $fileCollectionResult = $queryBuilder->execute();
        while ($fileCollectionUid = (int)$fileCollectionResult->fetchOne()) {
            $this->registerRecordForCacheFlushing('sys_file_collection', $fileCollectionUid);
        }
    }

    /**
     * Registers the given file for cache flushing.
     */
    protected function registerFileForCacheFlush(File $file): void
    {
        $this->registerRecordForCacheFlushing('sys_file', $file->getUid());
    }

    /**
     * Registers the given page UID in the array of pages for which the
     * cache should be flushed at the end.
     */
    protected function registerRecordForCacheFlushing(string $table, int $uid): void
    {
        if ($this->cacheOptimizerRegistry->isProcessedRecord($table, $uid)) {
            return;
        }
        $this->cacheOptimizerRegistry->registerProcessedRecord($table, $uid);
        $this->flushCacheTags[] = $table . '_' . $uid;
    }

    /** @noinspection PhpSameParameterValueInspection */
    private function getQueryBuilderForTable(string $tableName): QueryBuilder
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        return $connectionPool->getQueryBuilderForTable($tableName);
    }
}

<?php

declare(strict_types=1);

namespace Tx\Cacheopt\Tests\Functional;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "cacheopt".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Cacheopt\CacheOptimizerFiles;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the files cache optimizer.
 */
class CacheOptimizerFilesTest extends CacheOptimizerTestAbstract
{
    public const FILE_IDENTIFIER_REFERENCED = '/testdirectory/testfile_referenced.txt';

    public const FILE_IDENTIFIER_REFERENCED_IN_DIRECTORY = '/testdirectory_referenced/file_in_referenced_dir.txt';

    public const PAGE_UID_REFERENCING_CONTENT_REFERENCING_DIRECTORY = 1310;

    public const RESOURCE_STORAGE_UID = 1;

    protected CacheOptimizerFiles $cacheOptimizerFiles;

    protected ExtendedFileUtility $fileProcessor;

    protected StorageRepository $storageRepository;

    /**
     * Initializes required classes.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRepository = $this->getContainer()->get(StorageRepository::class);
        $this->fileProcessor = GeneralUtility::makeInstance(ExtendedFileUtility::class);
        $this->cacheOptimizerFiles = $this->getContainer()->get(CacheOptimizerFiles::class);
        $this->initFileProcessor();

        $this->setUpBackendUserMain();
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($GLOBALS['BE_USER']);
    }

    /**
     * If a sys_file record is changed the directory of the file is detected
     * and the cache of all pages is cleared where a reference to this directory is
     * used in the content elements.
     */
    public function testFileChangeClearsCacheForPagesReferencingToTheDirectory(): void
    {
        $this->fillPageCache(self::PAGE_UID_REFERENCED_DIRECTORY);
        $this->fillPageCache(self::PAGE_UID_REFERENCING_CONTENT_REFERENCING_DIRECTORY);

        $this->uploadFile(dirname(self::FILE_IDENTIFIER_REFERENCED_IN_DIRECTORY) . '/new_uploaded_file.txt');

        $this->assertPageCacheIsEmpty(self::PAGE_UID_REFERENCED_DIRECTORY);
        $this->assertPageCacheIsEmpty(self::PAGE_UID_REFERENCING_CONTENT_REFERENCING_DIRECTORY);
    }

    /**
     * If a sys_file record is changed the the cache of all pages is cleared
     * where a reference to this file is used in the content elements.
     */
    public function testFileChangeClearsCacheForPagesReferencingToTheFile(): void
    {
        $this->fillPageCache(self::PAGE_UID_REFERENCED_FILE);

        $fileValues = [
            'editfile' => [
                [
                    'data' => 'testcontent_modified',
                    'target' => $this->getRootFolderIdentifier() . ltrim(self::FILE_IDENTIFIER_REFERENCED, '/'),
                ],
            ],
        ];

        $this->processFileArrayAndFlushCache($fileValues);
        $this->assertPageCacheIsEmpty(self::PAGE_UID_REFERENCED_FILE);
    }

    /**
     * If a sys_file record that is referenced by a page is overwritten by an upload
     * the cache of the page referencing the file should be cleared.
     */
    public function testFileUploadClearsCacheOfPageWhereOverwrittenFileIsReferenced(): void
    {
        $this->fillPageCache(self::PAGE_UID_REFERENCED_FILE);

        $this->uploadFile(self::FILE_IDENTIFIER_REFERENCED);

        $this->assertPageCacheIsEmpty(self::PAGE_UID_REFERENCED_FILE);
    }

    /**
     * Returns the default storage.
     */
    protected function getDefaultStorage(): ResourceStorage
    {
        return $this->storageRepository->findByUid(self::RESOURCE_STORAGE_UID);
    }

    /**
     * Returns the identifier of the storage root folder.
     */
    protected function getRootFolderIdentifier(): string
    {
        $storage = $this->getDefaultStorage();
        $folderIdentifier = '/';
        return $storage->getUid() . ':' . $folderIdentifier;
    }

    /**
     * Initializes the file processor.
     */
    protected function initFileProcessor(): void
    {
        $this->fileProcessor->setExistingFilesConflictMode(DuplicationBehavior::REPLACE);
    }

    /**
     * Lets the file processor process the given array and lets the cache
     * optimizer flush the cache for all collected pages.
     */
    protected function processFileArrayAndFlushCache(array $fileValues): void
    {
        $this->fileProcessor->start($fileValues);
        // @extensionScannerIgnoreLine False positive.
        $this->fileProcessor->processData();
    }

    protected function uploadFile(string $filePath): void
    {
        $uploadPosition = 'file1';

        $_FILES['upload_' . $uploadPosition] = [
            'name' => basename($filePath),
            'type' => 'text/plain',
            'tmp_name' => $this->instancePath . '/typo3temp/uploadfiles/testfile_referenced.txt',
            'size' => 31,
        ];

        $fileValues = [
            'upload' => [
                [
                    'data' => $uploadPosition,
                    'target' => $this->getRootFolderIdentifier()
                        . ltrim(dirname($filePath), '/'),
                ],
            ],
        ];

        $this->processFileArrayAndFlushCache($fileValues);
    }
}

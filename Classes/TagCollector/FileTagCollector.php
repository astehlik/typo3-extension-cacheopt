<?php

declare(strict_types=1);

namespace Tx\Cacheopt\TagCollector;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "cacheopt".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;

class FileTagCollector extends AbstractTagCollector
{
    public function collectTagsForPreGeneratePublicUrl(GeneratePublicUrlForResourceEvent $event): void
    {
        $frontendCacheCollector = $this->getFrontendCacheCollector();

        if ($frontendCacheCollector === null) {
            return;
        }

        $resourceObject = $event->getResource();

        $cacheTags = [];
        $file = null;
        if ($resourceObject instanceof File) {
            $file = $resourceObject;
        } elseif ($resourceObject instanceof FileReference) {
            $file = $resourceObject->getOriginalFile();
            $cacheTags[] = new CacheTag('sys_file_reference_' . $resourceObject->getUid());
        } elseif ($resourceObject instanceof ProcessedFile) {
            $file = $resourceObject->getOriginalFile();
            $cacheTags[] = new CacheTag('sys_file_processedfile_' . $resourceObject->getUid());
        }

        if ($file instanceof File) {
            $cacheTags[] = new CacheTag('sys_file_' . $file->getUid());
            $fileMetadata = $file->getMetaData()->get();
            $cacheTags[] = new CacheTag('sys_file_metadata_' . $fileMetadata['uid']);
        }

        $frontendCacheCollector->addCacheTags(...$cacheTags);
    }
}

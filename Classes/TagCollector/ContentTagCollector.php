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

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterContentObjectRendererInitializedEvent;

class ContentTagCollector extends AbstractTagCollector
{
    #[AsEventListener]
    public function __invoke(
        AfterContentObjectRendererInitializedEvent $event,
    ): void {
        $frontendCacheCollector = $this->getFrontendCacheCollector();

        if ($frontendCacheCollector === null) {
            return;
        }

        $parentObject = $event->getContentObjectRenderer();

        $cacheTags = [];

        /** @extensionScannerIgnoreLine - False positive */
        $contentData = $parentObject->data;

        $table = $parentObject->getCurrentTable();
        $uid = (int)($contentData['uid'] ?? 0);
        if ($table === '' || $uid === 0) {
            return;
        }

        $cacheTags[] = new CacheTag($table . '_' . $uid);

        if (array_key_exists('_LOCALIZED_UID', $contentData) && (int)$contentData['_LOCALIZED_UID'] !== 0) {
            $cacheTags[] = new CacheTag($table . '_' . $contentData['_LOCALIZED_UID']);
        }

        // @extensionScannerIgnoreLine - False positive
        $frontendCacheCollector->addCacheTags(...$cacheTags);
    }
}

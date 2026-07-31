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

use Psr\Http\Message\ServerRequestInterface;
use Tx\Cacheopt\CacheApi;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectPostInitHookInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ContentTagCollector implements ContentObjectPostInitHookInterface
{
    public function __construct(
        private readonly CacheApi $cacheApi,
    ) {}

    /**
     * Hook for post processing the initialization of ContentObjectRenderer.
     *
     * @param ContentObjectRenderer $parentObject Parent content object
     */
    public function postProcessContentObjectInitialization(
        ContentObjectRenderer &$parentObject
    ): void {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            return;
        }

        $contentData = $parentObject->data;
        $table = $parentObject->getCurrentTable();

        $this->cacheApi->registerRecordCacheTags($table, $contentData, $request);

        // The page itself is initialized as a content object too. Its own starttime/endtime
        // is already taken into account by TYPO3 core's CacheLifetimeCalculator, and pages
        // rendered as part of a menu should not reduce the cache lifetime of the current page.
        if ($table !== 'pages') {
            $this->cacheApi->registerRecordCacheLifetime($table, $contentData);
        }
    }
}

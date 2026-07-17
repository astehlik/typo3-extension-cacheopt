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

use Tx\Cacheopt\Cache\ContentLifetimeRegistry;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectPostInitHookInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class ContentTagCollector extends AbstractTagCollector implements ContentObjectPostInitHookInterface
{
    public function __construct(
        private readonly ContentLifetimeRegistry $contentLifetimeRegistry,
    ) {}

    /**
     * Hook for post processing the initialization of ContentObjectRenderer.
     *
     * @param ContentObjectRenderer $parentObject Parent content object
     */
    public function postProcessContentObjectInitialization(
        ContentObjectRenderer &$parentObject
    ): void {
        $tsfe = $this->getTypoScriptFrontendController();
        if (!$tsfe instanceof TypoScriptFrontendController) {
            return;
        }

        $cacheTags = [];
        $contentData = $parentObject->data;

        $table = $parentObject->getCurrentTable();
        $uid = (int)($contentData['uid'] ?? 0);
        if ($table === '' || $uid === 0) {
            return;
        }

        $cacheTags[] = $table . '_' . $uid;

        if (array_key_exists('_LOCALIZED_UID', $contentData) && (int)$contentData['_LOCALIZED_UID'] !== 0) {
            $cacheTags[] = $table . '_' . $contentData['_LOCALIZED_UID'];
        }

        $tsfe->addCacheTags($cacheTags);

        // The page itself is initialized as a content object too. Its own starttime/endtime
        // is already taken into account by TYPO3 core's CacheLifetimeCalculator, and pages
        // rendered as part of a menu should not reduce the cache lifetime of the current page.
        if ($table !== 'pages') {
            $this->registerLifetimeRestriction($table, $contentData);
        }
    }

    /**
     * Registers the record's starttime/endtime (if any) with the ContentLifetimeRegistry,
     * capping the page cache lifetime even if the record is rendered from a different pid
     * than the page (e.g. via a RECORDS content element or a shortcut), which TYPO3 core's
     * own cache lifetime calculation does not take into account in that case.
     */
    private function registerLifetimeRestriction(string $table, array $record): void
    {
        $enableColumns = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'] ?? [];

        foreach (['starttime', 'endtime'] as $field) {
            $columnName = $enableColumns[$field] ?? null;
            if (!is_string($columnName) || $columnName === '' || !array_key_exists($columnName, $record)) {
                continue;
            }

            $this->contentLifetimeRegistry->registerTimestamp((int)$record[$columnName]);
        }
    }
}

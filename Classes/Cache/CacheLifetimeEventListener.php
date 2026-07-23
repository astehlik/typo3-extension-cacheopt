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

use TYPO3\CMS\Frontend\Event\ModifyCacheLifetimeForPageEvent;

/**
 * Caps the page cache lifetime according to the starttime/endtime values
 * collected by the ContentTagCollector while the page was rendered.
 */
class CacheLifetimeEventListener
{
    public function __construct(
        private readonly ContentLifetimeRegistry $contentLifetimeRegistry,
    ) {}

    public function __invoke(ModifyCacheLifetimeForPageEvent $event): void
    {
        $minimumTimestamp = $this->contentLifetimeRegistry->getMinimumTimestamp();
        if ($minimumTimestamp === PHP_INT_MAX) {
            return;
        }

        // EXEC_TIME (not ACCESS_TIME, which is rounded down to the current minute) is used
        // here because Typo3DatabaseBackend::set() adds the lifetime to EXEC_TIME to compute
        // the cache entry's expiry, so this must match exactly to avoid the cache outliving
        // the record's endtime. +1 second to make sure the cache is definitely regenerated.
        $lifetime = $minimumTimestamp - (int)$GLOBALS['EXEC_TIME'] + 1;

        $event->setCacheLifetime(min($event->getCacheLifetime(), $lifetime));
    }
}

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

/**
 * Functional tests for the content element tag collector.
 */
class ContentTagCollectorTest extends CacheOptimizerTestAbstract
{
    public const CONTENT_UID_REFERENCED = 95;

    public const PAGE_UID_REFERENCING_CONTENT = 137;

    public const PAGE_UID_REFERENCING_NESTED_CONTENT = 140;

    /**
     * The starttime/endtime restriction must also be picked up through a chain of nested
     * "records"/"shortcut" content elements, not just a single level of indirection. Page
     * PAGE_UID_REFERENCING_NESTED_CONTENT contains a shortcut (uid 98) that points to another
     * shortcut (uid 96, on a different page), which in turn points to CONTENT_UID_REFERENCED
     * (uid 95, on yet another page).
     */
    public function testEndtimeOfNestedReferencedContentCapsPageCacheLifetime(): void
    {
        $futureEndtime = $GLOBALS['EXEC_TIME'] + 3600;

        $this->getActionService()->modifyRecord(
            'tt_content',
            self::CONTENT_UID_REFERENCED,
            ['endtime' => $futureEndtime]
        );

        $this->fillPageCache(self::PAGE_UID_REFERENCING_NESTED_CONTENT);

        $expires = $this->getPageCacheExpires(self::PAGE_UID_REFERENCING_NESTED_CONTENT);

        self::assertGreaterThan($GLOBALS['EXEC_TIME'] + 1800, $expires);
        self::assertLessThanOrEqual($futureEndtime + 300, $expires);
    }

    /**
     * TYPO3 core only takes the starttime/endtime of tt_content records into account for the
     * page cache lifetime if they reside on the page that is being cached. If a content element
     * is referenced from another page (e.g. via a "shortcut" or "records" content element), its
     * timing is ignored by core and the page cache would only expire after the default cache
     * period, even though the referenced record's visibility already changed.
     *
     * Cacheopt closes this gap by tagging the page cache with the calculated lifetime of every
     * rendered record, regardless of the page it originates from.
     */
    public function testEndtimeOfReferencedContentCapsPageCacheLifetime(): void
    {
        $futureEndtime = $GLOBALS['EXEC_TIME'] + 3600;

        $this->getActionService()->modifyRecord(
            'tt_content',
            self::CONTENT_UID_REFERENCED,
            ['endtime' => $futureEndtime]
        );

        $this->fillPageCache(self::PAGE_UID_REFERENCING_CONTENT);

        $expires = $this->getPageCacheExpires(self::PAGE_UID_REFERENCING_CONTENT);

        // The cache must expire close to the referenced record's endtime and clearly
        // before the default 24h cache period, allowing some slack for the time that
        // passes between computing $futureEndtime and the page being rendered.
        self::assertGreaterThan($GLOBALS['EXEC_TIME'] + 1800, $expires);
        self::assertLessThanOrEqual($futureEndtime + 300, $expires);
    }
}

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
 * Tests the Xclass overrides that cap the page cache lifetime for records
 * hidden by a future starttime, referenced via shortcut or CONTENT cObject.
 */
class HiddenContentDiscoveryTest extends CacheOptimizerTestAbstract
{
    public const CONTENT_UID_HIDDEN = 99;

    public const PAGE_UID_REFERENCING_HIDDEN_CONTENT_VIA_CONTENT_COBJECT = 144;

    public const PAGE_UID_REFERENCING_HIDDEN_CONTENT_VIA_RECORDS_TYPOSCRIPT = 145;

    public const PAGE_UID_REFERENCING_HIDDEN_CONTENT_VIA_SHORTCUT = 143;

    /**
     * Content on page 144 selects hidden content on page 142 via a CONTENT cObject.
     */
    public function testStarttimeOfContentReferencedViaContentObjectCapsPageCacheLifetime(): void
    {
        $futureStarttime = $GLOBALS['EXEC_TIME'] + 3600;

        $this->getActionService()->modifyRecord(
            'tt_content',
            self::CONTENT_UID_HIDDEN,
            ['starttime' => $futureStarttime],
        );

        $this->fillPageCache(self::PAGE_UID_REFERENCING_HIDDEN_CONTENT_VIA_CONTENT_COBJECT);

        $expires = $this->getPageCacheExpires(self::PAGE_UID_REFERENCING_HIDDEN_CONTENT_VIA_CONTENT_COBJECT);

        $this->assertGreaterThan($GLOBALS['EXEC_TIME'] + 1800, $expires);
        $this->assertLessThanOrEqual($futureStarttime + 300, $expires);
    }

    /**
     * A plain RECORDS TypoScript object (not the built-in CType=shortcut wrapper) on page
     * 145 lists 4 items via its "records" field: 3 visible content elements plus
     * CONTENT_UID_HIDDEN. This mirrors a real editor-configured "Insert Records" element,
     * as opposed to relying on fluid_styled_content's Shortcut.typoscript FLUIDTEMPLATE
     * wrapper.
     */
    public function testStarttimeOfContentReferencedViaRecordsTypoScriptCapsPageCacheLifetime(): void
    {
        $futureStarttime = $GLOBALS['EXEC_TIME'] + 3600;

        $this->getActionService()->modifyRecord(
            'tt_content',
            self::CONTENT_UID_HIDDEN,
            ['starttime' => $futureStarttime],
        );

        $this->fillPageCache(self::PAGE_UID_REFERENCING_HIDDEN_CONTENT_VIA_RECORDS_TYPOSCRIPT);

        $expires = $this->getPageCacheExpires(self::PAGE_UID_REFERENCING_HIDDEN_CONTENT_VIA_RECORDS_TYPOSCRIPT);

        $this->assertGreaterThan($GLOBALS['EXEC_TIME'] + 1800, $expires);
        $this->assertLessThanOrEqual($futureStarttime + 300, $expires);
    }

    /**
     * A shortcut on page 143 points to hidden content on another page. Its "records" field
     * lists 4 items: 3 visible content elements plus CONTENT_UID_HIDDEN, matching a
     * multi-item real-world "Insert Records" configuration.
     */
    public function testStarttimeOfContentReferencedViaShortcutCapsPageCacheLifetime(): void
    {
        $futureStarttime = $GLOBALS['EXEC_TIME'] + 3600;

        $this->getActionService()->modifyRecord(
            'tt_content',
            self::CONTENT_UID_HIDDEN,
            ['starttime' => $futureStarttime],
        );

        $this->fillPageCache(self::PAGE_UID_REFERENCING_HIDDEN_CONTENT_VIA_SHORTCUT);

        $expires = $this->getPageCacheExpires(self::PAGE_UID_REFERENCING_HIDDEN_CONTENT_VIA_SHORTCUT);

        $this->assertGreaterThan($GLOBALS['EXEC_TIME'] + 1800, $expires);
        $this->assertLessThanOrEqual($futureStarttime + 300, $expires);
    }
}

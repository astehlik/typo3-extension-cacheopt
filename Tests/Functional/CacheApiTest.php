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

use Tx\CacheoptTest\Controller\CacheApiUsageController;

/**
 * Functional test verifying that CacheApi::registerRecord() can be used directly
 * by third-party extensions, independently of TYPO3 core's automatic
 * ContentObjectRenderer tagging.
 *
 * The granular registerRecordCacheTags()/registerRecordCacheLifetime() methods
 * are covered by unit tests.
 */
class CacheApiTest extends CacheOptimizerTestAbstract
{
    public const PAGE_UID_REGISTER_RECORD = 141;

    public function testRegisterRecordAddsCacheTagAndCapsPageCacheLifetime(): void
    {
        $this->fillPageCache(self::PAGE_UID_REGISTER_RECORD);

        $this->assertContains(
            CacheApiUsageController::TABLE . '_' . CacheApiUsageController::UID_REGISTER_RECORD,
            $this->getPageCacheTags(self::PAGE_UID_REGISTER_RECORD),
        );

        $expires = $this->getPageCacheExpires(self::PAGE_UID_REGISTER_RECORD);
        $this->assertGreaterThan($GLOBALS['EXEC_TIME'] + 1800, $expires);
        $this->assertLessThanOrEqual($GLOBALS['EXEC_TIME'] + 3600 + 300, $expires);
    }
}

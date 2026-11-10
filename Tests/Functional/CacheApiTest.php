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

use Tx\Cacheopt\CacheApi;
use Tx\Cacheopt\CacheOptimizerRegistry;
use Tx\CacheoptTest\Controller\CacheApiUsageController;

/**
 * Functional test verifying that CacheApi::registerRecord() and flushCacheForTable() can be used
 * directly by third-party extensions, independently of the ContentTagCollector and DataHandler hooks.
 *
 * The granular registerRecordCacheTags()/registerRecordCacheLifetime() methods
 * are covered by unit tests.
 */
class CacheApiTest extends CacheOptimizerTestAbstract
{
    public const PAGE_UID_REGISTER_RECORD = 141;

    public function testFlushCacheForTableFlushesPagesContainingRegisteredTypesOnly(): void
    {
        $this->fillPageCache(CacheOptimizerDataHandlerTest::PAGE_UID_CONTAINING_EXT_PLUGIN);
        $this->fillPageCache(CacheOptimizerDataHandlerTest::PAGE_UID_CONTAINING_EXT_CONTENT);
        $this->fillPageCache(CacheOptimizerDataHandlerTest::PAGE_UID_NORMAL);

        $this->get(CacheApi::class)->flushCacheForTable('tx_cacheopttest_domain_model_record');

        $this->assertPageCacheIsEmpty(CacheOptimizerDataHandlerTest::PAGE_UID_CONTAINING_EXT_PLUGIN);
        $this->assertPageCacheIsEmpty(CacheOptimizerDataHandlerTest::PAGE_UID_CONTAINING_EXT_CONTENT);
        $this->assertPageCacheIsFilled(CacheOptimizerDataHandlerTest::PAGE_UID_NORMAL);
    }

    public function testInjectedRegistrySharesRegistrationsMadeViaGetInstance(): void
    {
        CacheOptimizerRegistry::getInstance()->registerPluginForTable(
            'tx_cacheopttest_registered_at_runtime',
            'cacheopttest_recordrenderplugin'
        );
        $this->fillPageCache(CacheOptimizerDataHandlerTest::PAGE_UID_CONTAINING_EXT_PLUGIN);

        $this->get(CacheApi::class)->flushCacheForTable('tx_cacheopttest_registered_at_runtime');

        $this->assertPageCacheIsEmpty(CacheOptimizerDataHandlerTest::PAGE_UID_CONTAINING_EXT_PLUGIN);
    }

    public function testRegisterRecordAddsCacheTagAndCapsPageCacheLifetime(): void
    {
        $this->fillPageCache(self::PAGE_UID_REGISTER_RECORD);

        self::assertContains(
            CacheApiUsageController::TABLE . '_' . CacheApiUsageController::UID_REGISTER_RECORD,
            $this->getPageCacheTags(self::PAGE_UID_REGISTER_RECORD)
        );

        $expires = $this->getPageCacheExpires(self::PAGE_UID_REGISTER_RECORD);
        self::assertGreaterThan($GLOBALS['EXEC_TIME'] + 1800, $expires);
        self::assertLessThanOrEqual($GLOBALS['EXEC_TIME'] + 3600 + 300, $expires);
    }
}

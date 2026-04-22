<?php

declare(strict_types=1);

namespace Tx\Cacheopt\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tx\Cacheopt\CacheOptimizerRegistry;

class CacheOptimizerRegistryTest extends TestCase
{
    private CacheOptimizerRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new CacheOptimizerRegistry();
    }

    public function testRegisterContentForTable(): void
    {
        $this->registry->registerContentForTable('tx_myext_mytable', 'myext_contenttype');
        $this->assertSame(['myext_contenttype'], $this->registry->getContentTypesForTable('tx_myext_mytable'));
    }

    public function testRegisterContentForTables(): void
    {
        $this->registry->registerContentForTables(['tx_myext_mytable1', 'tx_myext_mytable2'], 'myext_contenttype');
        $this->assertSame(['myext_contenttype'], $this->registry->getContentTypesForTable('tx_myext_mytable1'));
        $this->assertSame(['myext_contenttype'], $this->registry->getContentTypesForTable('tx_myext_mytable2'));
        $this->assertSame([], $this->registry->getContentTypesForTable('tx_myext_mytable3'));
    }

    public function testRegisterPagesWithFlushedCache(): void
    {
        $this->registry->registerPagesWithFlushedCache([123, 124]);
        $this->assertSame([123, 124], $this->registry->getFlushedCachePageUids());
        $this->assertTrue($this->registry->pageCacheIsFlushed(123));
        $this->assertTrue($this->registry->pageCacheIsFlushed(124));
        $this->assertFalse($this->registry->pageCacheIsFlushed(125));
    }

    public function testRegisterPageWithFlushedCache(): void
    {
        $this->registry->registerPageWithFlushedCache(123);
        $this->assertSame([123], $this->registry->getFlushedCachePageUids());
        $this->assertTrue($this->registry->pageCacheIsFlushed(123));
    }

    public function testRegisterProcessedFolder(): void
    {
        $this->registry->registerProcessedFolder(123, 'myext_directoryidentifier');
        $this->assertTrue($this->registry->isProcessedFolder(123, 'myext_directoryidentifier'));
        $this->assertFalse($this->registry->isProcessedFolder(12, 'myext_directoryidentifier'));
        $this->assertFalse($this->registry->isProcessedFolder(123, 'myext_directoryidentifier1'));
    }

    public function testRegisterProcessedRecord(): void
    {
        $this->registry->registerProcessedRecord('tx_myext_mytable', 123);
        $this->assertTrue($this->registry->isProcessedRecord('tx_myext_mytable', 123));
        $this->assertFalse($this->registry->isProcessedRecord('tx_myext_mytable1', 123));
        $this->assertFalse($this->registry->isProcessedRecord('tx_myext_mytable', 12));
    }
}

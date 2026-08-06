<?php

declare(strict_types=1);

namespace Tx\Cacheopt\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tx\Cacheopt\CacheApi;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator;

class CacheApiTest extends TestCase
{
    private CacheApi $cacheApi;

    private CacheLifetimeCalculator&MockObject $cacheLifetimeCalculator;

    protected function setUp(): void
    {
        $this->cacheLifetimeCalculator = $this->createMock(CacheLifetimeCalculator::class);
        $this->cacheApi = new CacheApi($this->cacheLifetimeCalculator);
    }

    public function testRegisterRecordCacheLifetimeRestrictsMaximumLifetime(): void
    {
        $record = [
            'uid' => 5,
            'endtime' => 12345,
        ];

        $this->cacheLifetimeCalculator->expects($this->once())
            ->method('calculateLifetimeForRow')
            ->with('tt_content', $record)
            ->willReturn(3600);

        $cacheCollector = new CacheDataCollector();

        $this->cacheApi->registerRecordCacheLifetime('tt_content', $record, $this->buildRequest($cacheCollector));

        $this->assertSame(3600, $cacheCollector->resolveLifetime());
    }

    public function testRegisterRecordCacheTagsAddsCacheTagForLocalizedRecord(): void
    {
        $cacheCollector = new CacheDataCollector();

        $this->cacheApi->registerRecordCacheTags(
            'tt_content',
            [
                'uid' => 5,
                '_LOCALIZED_UID' => 9,
            ],
            $this->buildRequest($cacheCollector),
        );

        $this->assertSame(
            [
                'tt_content_5',
                'tt_content_9',
            ],
            $this->getCacheTagNames($cacheCollector),
        );
    }

    public function testRegisterRecordCacheTagsAddsCacheTagForRecord(): void
    {
        $cacheCollector = new CacheDataCollector();

        $this->cacheApi->registerRecordCacheTags('tt_content', ['uid' => 5], $this->buildRequest($cacheCollector));

        $this->assertSame(['tt_content_5'], $this->getCacheTagNames($cacheCollector));
    }

    public function testRegisterRecordCacheTagsDoesNothingForRecordWithoutUid(): void
    {
        $cacheCollector = new CacheDataCollector();

        $this->cacheApi->registerRecordCacheTags('tt_content', [], $this->buildRequest($cacheCollector));

        $this->assertSame([], $cacheCollector->getCacheTags());
    }

    public function testRegisterRecordCacheTagsDoesNothingWithoutFrontendCacheCollector(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);

        // @extensionScannerIgnoreLine - False positive, this is PHPUnit's mock builder with(),
        // unrelated to CacheHashCalculator.
        $request->method('getAttribute')->with('frontend.cache.collector')->willReturn(null);

        $this->cacheApi->registerRecordCacheTags('tt_content', ['uid' => 5], $request);

        $this->addToAssertionCount(1);
    }

    private function buildRequest(CacheDataCollector $cacheCollector): ServerRequestInterface&MockObject
    {
        $request = $this->createMock(ServerRequestInterface::class);

        // @extensionScannerIgnoreLine - False positive, this is PHPUnit's mock builder with(),
        // unrelated to CacheHashCalculator.
        $request->method('getAttribute')->with('frontend.cache.collector')->willReturn($cacheCollector);

        return $request;
    }

    /**
     * @return string[]
     */
    private function getCacheTagNames(CacheDataCollector $cacheCollector): array
    {
        return array_map(static fn(CacheTag $cacheTag): string => $cacheTag->name, $cacheCollector->getCacheTags());
    }
}

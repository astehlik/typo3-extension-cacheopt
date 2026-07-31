<?php

declare(strict_types=1);

namespace Tx\Cacheopt\Tests\Unit;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "cacheopt".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tx\Cacheopt\Cache\ContentLifetimeRegistry;
use Tx\Cacheopt\CacheApi;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class CacheApiTest extends TestCase
{
    private CacheApi $cacheApi;

    private ContentLifetimeRegistry&MockObject $contentLifetimeRegistry;

    protected function setUp(): void
    {
        $this->contentLifetimeRegistry = $this->createMock(ContentLifetimeRegistry::class);
        $this->cacheApi = new CacheApi($this->contentLifetimeRegistry);
    }

    public function testRegisterRecordCacheLifetimeDelegatesToContentLifetimeRegistry(): void
    {
        $record = [
            'uid' => 5,
            'endtime' => 12345,
        ];

        $this->contentLifetimeRegistry->expects(self::once())
            ->method('registerLifetimeRestriction')
            ->with('tt_content', $record);

        $this->cacheApi->registerRecordCacheLifetime('tt_content', $record);
    }

    public function testRegisterRecordCacheTagsAddsCacheTagForLocalizedRecord(): void
    {
        $frontendController = $this->createMock(TypoScriptFrontendController::class);
        $frontendController->expects(self::once())
            ->method('addCacheTags')
            ->with(['tt_content_5', 'tt_content_9']);

        $this->cacheApi->registerRecordCacheTags(
            'tt_content',
            [
                'uid' => 5,
                '_LOCALIZED_UID' => 9,
            ],
            $this->buildRequest($frontendController)
        );
    }

    public function testRegisterRecordCacheTagsAddsCacheTagForRecord(): void
    {
        $frontendController = $this->createMock(TypoScriptFrontendController::class);
        $frontendController->expects(self::once())
            ->method('addCacheTags')
            ->with(['tt_content_5']);

        $this->cacheApi->registerRecordCacheTags(
            'tt_content',
            ['uid' => 5],
            $this->buildRequest($frontendController)
        );
    }

    public function testRegisterRecordCacheTagsDoesNothingForRecordWithoutUid(): void
    {
        $frontendController = $this->createMock(TypoScriptFrontendController::class);
        $frontendController->expects(self::never())->method('addCacheTags');

        $this->cacheApi->registerRecordCacheTags('tt_content', [], $this->buildRequest($frontendController));
    }

    public function testRegisterRecordCacheTagsDoesNothingWithoutFrontendController(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('frontend.controller')->willReturn(null);

        $this->cacheApi->registerRecordCacheTags('tt_content', ['uid' => 5], $request);

        $this->addToAssertionCount(1);
    }

    private function buildRequest(TypoScriptFrontendController $frontendController): MockObject&ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('frontend.controller')->willReturn($frontendController);

        return $request;
    }
}

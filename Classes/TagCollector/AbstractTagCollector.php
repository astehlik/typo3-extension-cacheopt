<?php

declare(strict_types=1);

namespace Tx\Cacheopt\TagCollector;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheDataCollector;

abstract class AbstractTagCollector
{
    protected function getFrontendCacheCollector(): ?CacheDataCollector
    {
        $cacheCollector = $this->getRequest()?->getAttribute('frontend.cache.collector');

        return $cacheCollector instanceof CacheDataCollector ? $cacheCollector : null;
    }

    protected function getRequest(): ?ServerRequestInterface
    {
        $typo3Request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        return $typo3Request instanceof ServerRequestInterface ? $typo3Request : null;
    }
}

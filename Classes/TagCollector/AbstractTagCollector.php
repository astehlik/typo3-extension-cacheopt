<?php

declare(strict_types=1);

namespace Tx\Cacheopt\TagCollector;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

abstract class AbstractTagCollector
{
    protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        $typo3Request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        if (!$typo3Request instanceof ServerRequestInterface) {
            return null;
        }

        $frontendController = $typo3Request->getAttribute('frontend.controller');

        if (!$frontendController instanceof TypoScriptFrontendController) {
            return null;
        }

        return $frontendController;
    }
}

<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tx\Cacheopt\CacheOptimizerFiles;
use Tx\Cacheopt\TagCollector\FileTagCollector;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileContentsSetEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileCopiedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileCreatedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMovedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileRenamedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $container): void {
    $cacheOptimizerFiles = $container->registerForAutoconfiguration(CacheOptimizerFiles::class);

    $eventsHandlerMethods = [
        /** @uses CacheOptimizerFiles::handleFileAddPost() */
        AfterFileAddedEvent::class => 'handleFileAddPost',
        /** @uses CacheOptimizerFiles::handleFileSetContentsPost() */
        AfterFileContentsSetEvent::class => 'handleFileSetContentsPost',
        /** @uses CacheOptimizerFiles::handleFileCopyPost() */
        AfterFileCopiedEvent::class => 'handleFileCopyPost',
        /** @uses CacheOptimizerFiles::handleFileCreatePost() */
        AfterFileCreatedEvent::class => 'handleFileCreatePost',
        /** @uses CacheOptimizerFiles::handleFileDeletePost() */
        AfterFileDeletedEvent::class => 'handleFileDeletePost',
        /** @uses CacheOptimizerFiles::handleFileMovePost() */
        AfterFileMovedEvent::class => 'handleFileMovePost',
        /** @uses CacheOptimizerFiles::handleFileRenamePost() */
        AfterFileRenamedEvent::class => 'handleFileRenamePost',
        /** @uses CacheOptimizerFiles::handleFileReplacePost() */
        AfterFileReplacedEvent::class => 'handleFileReplacePost',
    ];

    foreach ($eventsHandlerMethods as $eventClassName => $handlerMethod) {
        $cacheOptimizerFiles
            ->addTag(
                'event.listener',
                [
                    'event' => $eventClassName,
                    'method' => $handlerMethod,
                    'identifier' => 'cacheopt/cache-optimizer-files-' . strtolower($handlerMethod),
                ]
            );
    }

    $fileTagCollector = $container->registerForAutoconfiguration(FileTagCollector::class);
    $fileTagCollector->addTag(
        'event.listener',
        [
            'event' => GeneratePublicUrlForResourceEvent::class,
            /** @uses FileTagCollector::collectTagsForPreGeneratePublicUrl() */
            'method' => 'collectTagsForPreGeneratePublicUrl',
            'identifier' => 'cacheopt/file-tag-collector',
        ]
    );
};

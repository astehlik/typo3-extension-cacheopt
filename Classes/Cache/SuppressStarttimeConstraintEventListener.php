<?php

declare(strict_types=1);

namespace Tx\Cacheopt\Cache;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "cacheopt".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 */

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Domain\Event\ModifyDefaultConstraintsForDatabaseQueryEvent;

/**
 * Strips the "starttime" constraint while StarttimeDiscoveryState is active,
 * so ContentObjectRenderer's discovery query can find records hidden by a
 * future starttime.
 */
class SuppressStarttimeConstraintEventListener
{
    public function __construct(
        private readonly StarttimeDiscoveryState $state,
    ) {}

    #[AsEventListener(identifier: 'cacheopt/suppress-starttime-constraint-event-listener')]
    public function __invoke(ModifyDefaultConstraintsForDatabaseQueryEvent $event): void
    {
        if (!$this->state->isActive()) {
            return;
        }

        $constraints = $event->getConstraints();
        unset($constraints['starttime']);
        $event->setConstraints($constraints);
    }
}

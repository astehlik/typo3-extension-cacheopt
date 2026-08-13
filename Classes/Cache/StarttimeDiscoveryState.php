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

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Shared flag between the ContentObjectRenderer Xclass and
 * SuppressStarttimeConstraintEventListener: active while the Xclass runs its
 * own starttime-discovery query, so the listener knows to strip the
 * starttime restriction only from that query, not the real one.
 */
class StarttimeDiscoveryState implements SingletonInterface
{
    private bool $active = false;

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}

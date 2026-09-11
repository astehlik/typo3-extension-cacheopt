<?php

declare(strict_types=1);

namespace Tx\Cacheopt;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "cacheopt".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A content or plugin type registered for a table, optionally restricted
 * to the records accepted by a filter.
 */
final class TypeRegistration
{
    /**
     * @param ?\Closure(array $record): bool $recordFilter
     */
    public function __construct(
        public readonly string $type,
        public readonly ?\Closure $recordFilter = null,
    ) {}

    public function hasRecordFilter(): bool
    {
        return $this->recordFilter !== null;
    }

    /**
     * A NULL record (e.g. one that could not be loaded) only matches registrations without a filter.
     */
    public function matchesRecord(?array $record): bool
    {
        if ($this->recordFilter === null) {
            return true;
        }

        return $record !== null && ($this->recordFilter)($record);
    }
}

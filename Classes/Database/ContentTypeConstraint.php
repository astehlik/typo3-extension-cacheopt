<?php

declare(strict_types=1);

namespace Tx\Cacheopt\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "cacheopt".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * @internal
 */
final class ContentTypeConstraint
{
    /**
     * Restricts the query to tt_content elements that have one of the given content types or plugin types.
     *
     * @param string[] $contentTypes
     * @param string[] $pluginTypes
     */
    public static function apply(array $contentTypes, array $pluginTypes, QueryBuilder $queryBuilder): void
    {
        $orStatements = [];

        if ($contentTypes !== []) {
            $orStatements[] = $queryBuilder->expr()->in(
                'tt_content.CType',
                $queryBuilder->createNamedParameter($contentTypes, ArrayParameterType::STRING)
            );
        }

        if ($pluginTypes !== []) {
            $orStatements[] = $queryBuilder->expr()->and(
                $queryBuilder->expr()->eq('tt_content.CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->in(
                    'tt_content.list_type',
                    $queryBuilder->createNamedParameter($pluginTypes, ArrayParameterType::STRING)
                )
            );
        }

        if (count($orStatements) === 1) {
            $queryBuilder->andWhere($orStatements[0]);
            return;
        }

        $queryBuilder->andWhere($queryBuilder->expr()->or(...$orStatements));
    }
}

<?php

/*
 * This file is part of the ESQL project.
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\Query;

trait CategoriesTrait
{
    public function getCategoriesCTE(string $categoryPredicate = 'c.identifier = :category', bool $descendant = false): string
    {
        $ctePredicate = $descendant ? 'c.identifier = categories.parent_id' : 'c.parent_id = categories.identifier';

        return <<<SQL
WITH categories(identifier, name, parent_id) AS (
    SELECT c.identifier, c.name, c.parent_id FROM category c WHERE {$categoryPredicate}
    UNION ALL
    SELECT c.identifier, c.name, c.parent_id FROM categories, category c WHERE {$ctePredicate}
)
SQL;
    }
}

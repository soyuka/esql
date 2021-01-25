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

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Doctrine\Persistence\ManagerRegistry;
use Soyuka\ESQL\ESQL;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Category;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Product;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Query\CategoriesTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ProductDataProvider implements RestrictedDataProviderInterface, CollectionDataProviderInterface, ContextAwareCollectionDataProviderInterface
{
    use CategoriesTrait;

    private RequestStack $requestStack;
    private ManagerRegistry $managerRegistry;
    private ESQLMapperInterface $mapper;
    private ESQLInterface $esql;
    private ContextAwareCollectionDataProviderInterface $decorated;

    public function __construct(RequestStack $requestStack, ManagerRegistry $managerRegistry, ESQLMapperInterface $mapper, ESQLInterface $esql, ContextAwareCollectionDataProviderInterface $decorated)
    {
        $this->requestStack = $requestStack;
        $this->managerRegistry = $managerRegistry;
        $this->mapper = $mapper;
        $this->esql = $esql;
        $this->decorated = $decorated;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Product::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $connection = $this->managerRegistry->getConnection();
        ['table' => $table, 'columns' => $columns] = $this->esql->__invoke($resourceClass);

        $data = $this->decorated->getCollection($resourceClass, $operationName, $context);
        $categories = $this->getCategories();

        foreach ($data as $product) {
            foreach ($categories as $category) {
                if ($product->categoryRelation->identifier === $category->identifier) {
                    $product->categoryRelation = $category;
                }

                if ($product->categoryRelation->parent && $product->categoryRelation->parent->identifier === $category->identifier) {
                    $product->categoryRelation->parent = $category;
                }
            }
        }

        return $data;
    }

    private function getCategories(): array
    {
        $category = null === ($request = $this->requestStack->getCurrentRequest()) ? null : $request->query->get('category');
        /** @psalm-suppress DocblockTypeContradiction */
        if (\is_array($category)) {
            throw new BadRequestHttpException();
        }

        $connection = $this->managerRegistry->getConnection();
        $categoryPredicate = $category ? 'c.identifier = :category' : 'c.parent_id IS NULL';
        ['table' => $table, 'columns' => $columns, 'join' => $join] = $this->esql->__invoke(Category::class);
        $alias = ESQL::getAlias(Category::class);

        $query = <<<SQL
WITH
    ancestors(identifier, name, parent_id) AS (
        SELECT c.identifier, c.name, c.parent_id FROM category c WHERE {$categoryPredicate}
        UNION ALL
        SELECT c.identifier, c.name, c.parent_id FROM ancestors, category c WHERE c.identifier = ancestors.parent_id
    ),
    descendants(identifier, name, parent_id) AS (
        SELECT c.identifier, c.name, c.parent_id FROM category c WHERE {$categoryPredicate}
        UNION ALL
        SELECT c.identifier, c.name, c.parent_id FROM descendants, category c WHERE c.parent_id = descendants.identifier
    )
SELECT {$columns()} FROM ancestors {$alias}
UNION
SELECT {$columns()} FROM descendants {$alias}
SQL;

        $stmt = $connection->executeQuery($query, $category ? ['category' => $category] : []);
        $data = $stmt->fetchAll();
        $categories = $this->mapper->map($data, Category::class);

        foreach ($categories as $category) {
            if (null === $category->parent) {
                continue;
            }

            foreach ($categories as $parent) {
                if ($parent->identifier === $category->parent->identifier) {
                    $category->parent = $parent;
                }
            }
        }

        return $categories;
    }
}

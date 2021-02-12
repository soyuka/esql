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
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Category;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Product;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ProductDataProvider implements RestrictedDataProviderInterface, CollectionDataProviderInterface, ContextAwareCollectionDataProviderInterface
{
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
        $data = $this->decorated->getCollection($resourceClass, $operationName, $context);
        dd($data);
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
        $categoryParameter = null === ($request = $this->requestStack->getCurrentRequest()) ? null : $request->query->get('category');
        /** @psalm-suppress DocblockTypeContradiction */
        if (\is_array($categoryParameter)) {
            throw new BadRequestHttpException();
        }

        $connection = $this->managerRegistry->getConnection();
        $categoryPredicate = $categoryParameter ? 'c.identifier = :category' : 'c.parent_id IS NULL';
        $category = $this->esql->__invoke(Category::class);

        $query = <<<SQL
WITH RECURSIVE
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
SELECT {$category->columns()} FROM ancestors {$category->alias()}
UNION
SELECT {$category->columns()} FROM descendants {$category->alias()}
SQL;

        $stmt = $connection->executeQuery($query, $categoryParameter ? ['category' => $categoryParameter] : []);
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

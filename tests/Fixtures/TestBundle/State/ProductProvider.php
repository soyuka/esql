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

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\Persistence\ManagerRegistry;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Category;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ProductProvider implements ProviderInterface
{
    private readonly ESQLMapperInterface $mapper;

    public function __construct(private readonly RequestStack $requestStack, private readonly ManagerRegistry $managerRegistry, private readonly ESQLInterface $esql, private readonly ProviderInterface $decorated)
    {
    }

    private function getCategories(): array
    {
        /** @var array|string */
        $categoryParameter = null === ($request = $this->requestStack->getCurrentRequest()) ? null : $request->query->get('category');
        if (\is_array($categoryParameter)) {
            throw new BadRequestHttpException();
        }

        $connection = $this->managerRegistry->getConnection();
        $categoryPredicate = $categoryParameter ? 'c.identifier = :category' : 'c.parent_id IS NULL';
        $category = $this->esql->__invoke(Category::class);
        $recursive = $connection->getDriver()->getDatabasePlatform() instanceof SQLServerPlatform ? '' : ' RECURSIVE ';

        $query = <<<SQL
WITH{$recursive}
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
        $data = $stmt->fetchAllAssociative();
        $categories = $category->map($data);

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

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var array */
        $data = $this->decorated->provide($operation, $uriVariables, $context);
        if ($data instanceof PartialPaginatorInterface && !\count($data)) {
            return $data;
        }
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
}

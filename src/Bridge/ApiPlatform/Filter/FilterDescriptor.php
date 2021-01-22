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

namespace Soyuka\ESQL\Bridge\ApiPlatform\Filter;

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\PropertyInfo\Type;

class FilterDescriptor implements FilterInterface
{
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
    }

    public function getDescription(string $resourceClass): array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if (!$resourceMetadata->getAttribute('esql')) {
            return [];
        }

        $filters = [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property);

            if ($propertyMetadata->isIdentifier() || !$type = $propertyMetadata->getType()) {
                continue;
            }

            switch ($type->getBuiltinType()) {
                case Type::BUILTIN_TYPE_INT:
                case Type::BUILTIN_TYPE_FLOAT:
                    $filters[$property] = [
                        'property' => $property,
                        'type' => 'string',
                        'required' => false,
                        'description' => 'Numeric filters',
                        'openapi' => [
                            'examples' => [
                                'lt' => $this->getExample('Lower then', sprintf('%s=lt.10 or %1$s=not.lt.10', $property)),
                                'lte' => $this->getExample('Lower then or equal', sprintf('%s=lte.10 or %1$s=not.lte.10', $property)),
                                'gt' => $this->getExample('Greater then', sprintf('%s=gt.10 or %1$s=not.gt.10', $property)),
                                'gte' => $this->getExample('Greater then or equal', sprintf('%s=gte.10 or %1$s=not.gte.10', $property)),
                                'eq' => $this->getExample('Equals', sprintf('%s=eq.10 or %1$s=not.eq.10', $property)),
                                'neq' => $this->getExample('Not equals', sprintf('%s=neq.10', $property)),
                                'in' => $this->getExample('In', sprintf('%s=in(10,11,12) or %1$s=not.in(10,11,12)', $property)),
                            ],
                        ],
                    ];

                    break;
                case Type::BUILTIN_TYPE_STRING:
                    $filters[$property] = [
                        'property' => $property,
                        'type' => 'string',
                        'required' => false,
                        'description' => 'String filters',
                        'openapi' => [
                            'examples' => [
                                'eq' => $this->getExample('Equals', sprintf('%s=eq.foo or %1$s=not.eq.foo', $property)),
                                'neq' => $this->getExample('Equals', sprintf('%s=neq.foo', $property)),
                                'in' => $this->getExample('Equals', sprintf('%s=in(foo,bar) or %1$s=not.in(foo,bar)', $property)),
                                'like' => $this->getExample('Like, use * instead of %', sprintf('%s=like.*foo* or %1$s=not.like.*foo*', $property)),
                                'ilike' => $this->getExample('Like case insensitive, use * instead of %', sprintf('%s=ilike.*foo* or %1$s=not.ilike.*foo*', $property)),
                            ],
                        ],
                    ];
                    break;
                case Type::BUILTIN_TYPE_BOOL:
                    $filters[$property] = [
                        'property' => $property,
                        'type' => 'string',
                        'required' => false,
                        'description' => 'Boolean filters',
                        'openapi' => [
                            'examples' => [
                                'eq' => $this->getExample('Equals', sprintf('%s=eq.true or %1$s=not.eq.true', $property)),
                                'neq' => $this->getExample('Equals', sprintf('%s=neq.true', $property)),
                                'is' => $this->getExample('Is', sprintf('%s=is.true or %1$s=not.is.false', $property)),
                            ],
                        ],
                    ];
            }

            if (isset($filters[$property]) && $type->isNullable()) {
                $filters[$property]['openapi']['examples']['is'] = $this->getExample('Is null', sprintf('%s=is.null or %1$s=not.is.null', $property));
            }
        }

        return array_merge($filters, [
            'or' => [
                'property' => '',
                'type' => 'string',
                'required' => false,
                'description' => 'Composeable filter or',
                'openapi' => [
                    'example' => 'or(name.eq.1,price.lte.100)',
                ],
            ],
            'and' => [
                'property' => '',
                'type' => 'string',
                'required' => false,
                'description' => 'Composeable filter and',
                'openapi' => [
                    'example' => 'and(name.eq.1,price.lte.100)',
                ],
            ],
        ]);
    }

    private function getExample(string $example, string $value): array
    {
        return ['summary' => $example, 'description' => $example, 'value' => $value];
    }
}

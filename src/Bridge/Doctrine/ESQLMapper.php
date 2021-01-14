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

namespace Soyuka\ESQL\Bridge\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Jane\AutoMapper\AutoMapperInterface;
use LogicException;
use RuntimeException;
use Soyuka\ESQL\ESQL;
use Soyuka\ESQL\ESQLMapperInterface;

final class ESQLMapper implements ESQLMapperInterface
{
    private AutoMapperInterface $automapper;
    private ManagerRegistry $registry;

    public function __construct(AutoMapperInterface $automapper, ManagerRegistry $registry)
    {
        $this->automapper = $automapper;
        $this->registry = $registry;
    }

    public function map(array $data, string $resourceClass)
    {
        $memory = [];
        foreach ($data as $key => $value) {
            if (\is_int($key)) {
                $data[$key] = $this->map($value, $resourceClass);
                continue;
            }

            $aliasPos = strpos($key, '_');
            if (false === $aliasPos) {
                throw new LogicException('No alias found');
            }

            $alias = substr($key, 0, $aliasPos);
            $key = substr($key, $aliasPos + 1);
            $class = ESQL::getClass($alias);

            if (!isset($memory[$class])) {
                $memory[$class] = [];
            }

            $memory[$class][$key] = $value;
        }

        if (isset($memory[$resourceClass])) {
            $normalized = $memory[$resourceClass];
            unset($memory[$resourceClass]);
            foreach ($memory as $class => $value) {
                $normalized[$this->getRelationFieldName($resourceClass, $class)] = $value;
                unset($memory[$class]);
            }

            return $this->automapper->map($normalized, $resourceClass);
        }

        return $data;
    }

    private function getRelationFieldName(string $class, string $relationClass): string
    {
        $metadata = $this->getClassMetadata($class);
        $relationMetadata = $this->getClassMetadata($relationClass);

        foreach ($metadata->getAssociationMappings() as $fieldName => $association) {
            if ($association['targetEntity'] === $relationClass) {
                return $fieldName;
            }
        }

        throw new LogicException(sprintf('Relation between %s and %s was not found.', $metadata->name, $relationMetadata->name));
    }

    private function getClassMetadata(string $class): ClassMetadataInfo
    {
        $manager = $this->registry->getManagerForClass($class);
        if (!$manager) {
            throw new RuntimeException('No manager for class '.$class);
        }
        $classMetadata = $manager->getClassMetadata($class);
        if (!$classMetadata instanceof ClassMetadataInfo) {
            throw new RuntimeException('No class metadata for class '.$class);
        }

        return $classMetadata;
    }
}
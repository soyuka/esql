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

namespace Soyuka\ESQL\Bridge\Symfony\Serializer;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Soyuka\ESQL\ESQL;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;
use Soyuka\ESQL\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class ESQLMapper implements ESQLMapperInterface
{
    private DenormalizerInterface $normalizer;
    private ESQLInterface $esql;
    private ManagerRegistry $registry;

    public function __construct(DenormalizerInterface $normalizer, ESQLInterface $esql, ManagerRegistry $registry)
    {
        $this->normalizer = $normalizer;
        $this->esql = $esql;
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
                throw new RuntimeException('No alias found');
            }

            $alias = substr($key, 0, $aliasPos);
            $key = substr($key, $aliasPos + 1);
            $class = ESQL::getClass($alias);

            if (!isset($memory[$class])) {
                $memory[$class] = [];
            }

            if ($association = $this->getAssociation($class, $key)) {
                $memory[$class][$association['fieldName']] = $this->normalizer->denormalize([$association['sourceToTargetKeyColumns'][$key] => $value], $class, null, [ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]);
            } else {
                $memory[$class][$key] = $value;
            }
        }

        if (isset($memory[$resourceClass])) {
            $normalized = $memory[$resourceClass];
            unset($memory[$resourceClass]);
            foreach ($memory as $class => $value) {
                $normalized[$this->relationFieldName($resourceClass, $class)] = $this->normalizer->denormalize($value, $class, null, [ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]);
                unset($memory[$class]);
            }

            return $this->normalizer->denormalize($normalized, $resourceClass, null, [ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]);
        }

        return $data;
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

    private function relationFieldName(string $class, string $relationClass): string
    {
        $metadata = $this->getClassMetadata($class);
        $relationMetadata = $this->getClassMetadata($relationClass);
        foreach ($metadata->getAssociationMappings() as $fieldName => $association) {
            if ($association['targetEntity'] === $relationClass) {
                return $fieldName;
            }
        }

        throw new RuntimeException(sprintf('Relation between %s and %s was not found.', $metadata->name, $relationMetadata->name));
    }

    private function getAssociation(string $class, string $columnName): ?array
    {
        $metadata = $this->getClassMetadata($class);
        foreach ($metadata->getAssociationMappings() as $fieldName => $association) {
            foreach ($association['joinColumns'] ?? [] as $column) {
                if ($column['name'] === $columnName) {
                    return $association;
                }
            }
        }

        return null;
    }
}

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

use Soyuka\ESQL\ESQLAliasInterface;
use Soyuka\ESQL\ESQLMapper as Base;
use Soyuka\ESQL\ESQLMapperInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class ESQLMapper extends Base implements ESQLMapperInterface
{
    public function __construct(private readonly DenormalizerInterface $normalizer, private readonly PropertyInfoExtractorInterface $propertyInfo = new PropertyInfoExtractor([new ReflectionExtractor()], [new ReflectionExtractor()]))
    {
    }

    public function map(array $data, string $class, ESQLAliasInterface $a)
    {
        if (!\is_int(key($data))) {
            $array = $this->toArray($data, $a);

            foreach ($array as $property => $value) {
                if (\is_array($value) && $relationClass = $this->getObjectType($class, $property)) {
                    $array[$property] = $this->normalizer->denormalize($value, $relationClass);
                }
            }

            return $this->normalizer->denormalize($array, $class, null, [ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]);
        }

        foreach ($data as $key => $value) {
            $data[$key] = $this->map($value, $class, $a);
        }

        return $data;
    }

    private function getObjectType(string $class, string $property): ?string
    {
        $types = $this->propertyInfo->getTypes($class, $property);

        foreach ($types ?? [] as $type) {
            if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()) {
                return $type->getClassName();
            }
        }

        return null;
    }
}

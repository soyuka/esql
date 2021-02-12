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

use Soyuka\ESQL\ESQLMapper as Base;
use Soyuka\ESQL\ESQLMapperInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class ESQLMapper extends Base implements ESQLMapperInterface
{
    private DenormalizerInterface $normalizer;

    public function __construct(DenormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function map(array $data, string $class)
    {
        if (!\is_int(key($data))) {
            return $this->normalizer->denormalize($this->toArray($data), $class, null, [ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]);
        }

        foreach ($data as $key => $value) {
            $data[$key] = $this->normalizer->denormalize($this->toArray($value), $class, null, [ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]);
        }

        return $data;
    }
}

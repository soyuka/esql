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

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\AutoMapper;

use Jane\Component\AutoMapper\MapperMetadataInterface;
use Jane\Component\AutoMapper\Transformer\AbstractUniqueTypeTransformerFactory;
use Jane\Component\AutoMapper\Transformer\TransformerInterface;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Model\MonetaryAmount;
use Symfony\Component\PropertyInfo\Type;

final class MonetaryAmountTransformerFactory extends AbstractUniqueTypeTransformerFactory
{
    /**
     * {@inheritdoc}
     */
    protected function createTransformer(Type $sourceType, Type $targetType, MapperMetadataInterface $mapperMetadata): ?TransformerInterface
    {
        $isTargetMonetaryAmount = $this->isMonetaryAmountType($targetType);

        if ($isTargetMonetaryAmount && Type::BUILTIN_TYPE_ARRAY === $sourceType->getBuiltinType()) {
            return new MonetaryAmountTransformer();
        }

        return null;
    }

    private function isMonetaryAmountType(Type $type): bool
    {
        if (Type::BUILTIN_TYPE_OBJECT !== $type->getBuiltinType()) {
            return false;
        }

        if (MonetaryAmount::class !== $type->getClassName() && !is_subclass_of($type->getClassName(), MonetaryAmount::class)) {
            return false;
        }

        return true;
    }
}

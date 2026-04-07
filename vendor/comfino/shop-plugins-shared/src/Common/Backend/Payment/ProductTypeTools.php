<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Payment;

use Comfino\Api\Dto\Payment\LoanTypeEnum;

final class ProductTypeTools
{
    /**
     * @param string[] $productTypes
     * @return LoanTypeEnum[]
     */
    public static function getAsEnums(array $productTypes): array
    {
        return array_map(
            static function (string $productType): LoanTypeEnum {
                return LoanTypeEnum::from($productType);
            },
            $productTypes
        );
    }
}

<?php

declare(strict_types=1);

namespace Comfino\Api\Response;

use Comfino\Api\Dto\Payment\LoanTypeEnum;

class GetProductTypes extends Base
{
    public $productTypes;

    public $productTypesWithNames;

    /**
     * @param mixed[]|string|bool|null|float|int $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
        $this->checkResponseType($deserializedResponseBody, 'array');

        $this->productTypesWithNames = $deserializedResponseBody;
        $this->productTypes = array_map(
            static function (string $productType): LoanTypeEnum {
                return LoanTypeEnum::from($productType, false);
            },
            array_keys($deserializedResponseBody)
        );
    }
}

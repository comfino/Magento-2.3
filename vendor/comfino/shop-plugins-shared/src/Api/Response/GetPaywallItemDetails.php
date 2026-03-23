<?php

declare(strict_types=1);

namespace Comfino\Api\Response;

class GetPaywallItemDetails extends Base
{
    public $productDetails;

    public $listItemData;

    /**
     * @param mixed[]|string|bool|null|float|int $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
        $this->checkResponseType($deserializedResponseBody, 'array');
        $this->checkResponseStructure($deserializedResponseBody, ['productDetails', 'listItemData']);
        $this->checkResponseType($deserializedResponseBody['productDetails'], 'string');
        $this->checkResponseType($deserializedResponseBody['listItemData'], 'string');

        $this->productDetails = $deserializedResponseBody['productDetails'];
        $this->listItemData = $deserializedResponseBody['listItemData'];
    }
}

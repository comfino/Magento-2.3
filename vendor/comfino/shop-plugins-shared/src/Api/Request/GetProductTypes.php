<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Request;
use Comfino\FinancialProduct\ProductTypesListTypeEnum;

class GetProductTypes extends Request
{
    /**
     * @param ProductTypesListTypeEnum $listType
     */
    public function __construct(ProductTypesListTypeEnum $listType)
    {
        $this->setRequestMethod('GET');
        $this->setApiEndpointPath('product-types');
        $this->setRequestParams(['listType' => (string) $listType]);
    }

    protected function prepareRequestBody(): ?array
    {
        return null;
    }
}

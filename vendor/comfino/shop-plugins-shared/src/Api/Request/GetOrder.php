<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Request;

class GetOrder extends Request
{
    /**
     * @param string $orderId
     */
    public function __construct(string $orderId)
    {
        $this->setRequestMethod('GET');
        $this->setApiEndpointPath(sprintf('orders/%s', $orderId));
    }

    protected function prepareRequestBody(): ?array
    {
        return null;
    }
}

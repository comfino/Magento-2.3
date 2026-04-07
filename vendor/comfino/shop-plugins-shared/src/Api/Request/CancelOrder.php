<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Request;

class CancelOrder extends Request
{
    /**
     * @param string $orderId
     */
    public function __construct(string $orderId)
    {
        $this->setRequestMethod('PUT');
        $this->setApiEndpointPath(sprintf('orders/%s/cancel', $orderId));
    }

    protected function prepareRequestBody(): ?array
    {
        return null;
    }
}

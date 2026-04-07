<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Request;

use Comfino\Api\Request;

class NotifyAbandonedCart extends Request
{
    /**
     * @var string
     */
    private $type;
    public function __construct(string $type)
    {
        $this->type = $type;
        $this->setRequestMethod('POST');
        $this->setApiEndpointPath('abandoned_cart');
    }

    protected function prepareRequestBody(): ?array
    {
        return ['type' => $this->type];
    }
}

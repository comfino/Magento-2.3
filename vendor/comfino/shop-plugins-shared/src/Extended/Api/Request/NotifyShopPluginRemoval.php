<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Request;

use Comfino\Api\Request;

class NotifyShopPluginRemoval extends Request
{
    public function __construct()
    {
        $this->setRequestMethod('PUT');
        $this->setApiEndpointPath('log-plugin-remove');
    }

    protected function prepareRequestBody(): ?array
    {
        return null;
    }
}

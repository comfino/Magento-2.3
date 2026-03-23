<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Request;

class GetWidgetKey extends Request
{
    public function __construct()
    {
        $this->setRequestMethod('GET');
        $this->setApiEndpointPath('widget-key');
    }

    protected function prepareRequestBody(): ?array
    {
        return null;
    }
}

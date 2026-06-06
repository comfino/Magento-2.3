<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Request;

class GetCreditors extends Request
{
    public function __construct()
    {
        $this->setRequestMethod('GET');
        $this->setApiEndpointPath('creditors');
    }

    protected function prepareRequestBody(): ?array
    {
        return null;
    }
}

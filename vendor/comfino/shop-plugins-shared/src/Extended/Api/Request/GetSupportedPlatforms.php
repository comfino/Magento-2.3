<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Request;

use Comfino\Api\Request;

class GetSupportedPlatforms extends Request
{
    public function __construct()
    {
        $this->setRequestMethod('GET');
        $this->setApiEndpointPath('plugin-platforms');
    }

    protected function prepareRequestBody(): ?array
    {
        return null;
    }
}

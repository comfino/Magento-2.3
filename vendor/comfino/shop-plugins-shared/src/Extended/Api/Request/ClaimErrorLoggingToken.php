<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Request;

use Comfino\Api\Request;

class ClaimErrorLoggingToken extends Request
{
    public function __construct()
    {
        $this->setRequestMethod('POST');
        $this->setApiEndpointPath('error-logging-token');
    }

    protected function prepareRequestBody(): ?array
    {
        return null;
    }
}

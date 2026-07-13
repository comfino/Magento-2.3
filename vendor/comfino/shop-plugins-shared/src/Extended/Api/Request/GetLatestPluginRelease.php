<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Request;

use Comfino\Api\Request;

class GetLatestPluginRelease extends Request
{
    public function __construct(string $platform)
    {
        $this->setRequestMethod('GET');
        $this->setApiEndpointPath(sprintf('plugin-releases/%s/latest', $platform));
    }

    protected function prepareRequestBody(): ?array
    {
        return null;
    }
}

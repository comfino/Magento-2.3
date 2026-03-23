<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Request;

class IsShopAccountActive extends Request
{
    /**
     * @param string|null $cacheInvalidateUrl
     * @param string|null $configurationUrl
     */
    public function __construct(?string $cacheInvalidateUrl, ?string $configurationUrl)
    {
        $this->setRequestMethod('GET');
        $this->setApiEndpointPath('user/is-active');

        $requestHeaders = [];

        if (!empty($cacheInvalidateUrl)) {
            $requestHeaders['Comfino-Cache-Invalidate-Url'] = $cacheInvalidateUrl;
        }

        if (!empty($configurationUrl)) {
            $requestHeaders['Comfino-Configuration-Url'] = $configurationUrl;
        }

        if (count($requestHeaders) > 0) {
            $this->setRequestHeaders($requestHeaders);
        }
    }

    protected function prepareRequestBody(): ?array
    {
        return null;
    }
}

<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Request;
use Comfino\Api\UrlValidator;

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
            UrlValidator::assertValidCallbackUrl($cacheInvalidateUrl, 'Comfino-Cache-Invalidate-Url');

            $requestHeaders['Comfino-Cache-Invalidate-Url'] = $cacheInvalidateUrl;
        }

        if (!empty($configurationUrl)) {
            UrlValidator::assertValidCallbackUrl($configurationUrl, 'Comfino-Configuration-Url');

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

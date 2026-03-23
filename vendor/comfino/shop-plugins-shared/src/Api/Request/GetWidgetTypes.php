<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Request;

class GetWidgetTypes extends Request
{
    /**
     * @var bool
     */
    private $useNewApi;
    public function __construct(bool $useNewApi)
    {
        $this->useNewApi = $useNewApi;
        $this->setRequestMethod('GET');
        $this->setApiEndpointPath($this->useNewApi ? 'widget' : 'widget-types');
    }

    /**
     * @param string $apiHost
     * @param int $apiVersion
     */
    protected function getApiEndpointUri($apiHost, $apiVersion): string
    {
        if (!$this->useNewApi) {
            return parent::getApiEndpointUri($apiHost, $apiVersion);
        }

        $uri = implode('/', [trim($apiHost, " /\n\r\t\v\0"), $this->apiEndpointPath, "v$apiVersion/widget-types"]);

        if (!empty($this->requestParams)) {
            $uri .= ('?' . http_build_query($this->requestParams));
        }

        return $uri;
    }

    protected function prepareRequestBody(): ?array
    {
        return null;
    }
}

<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Factory;

use Comfino\Common\Backend\RestEndpointManager;
use Comfino\Extended\Api\Serializer\Json as JsonSerializer;
use ComfinoExternal\Sunrise\Http\Factory\ResponseFactory;
use ComfinoExternal\Sunrise\Http\Factory\ServerRequestFactory;
use ComfinoExternal\Sunrise\Http\Factory\StreamFactory;
use ComfinoExternal\Sunrise\Http\Factory\UriFactory;

final class ApiServiceFactory
{
    /**
     * @param string[] $apiKeys
     */
    public function createService(
        string $platformName,
        string $platformVersion,
        string $pluginVersion,
        array $apiKeys
    ): RestEndpointManager {
        return RestEndpointManager::getInstance(
            $platformName,
            $platformVersion,
            $pluginVersion,
            $apiKeys,
            new ServerRequestFactory(),
            new StreamFactory(),
            new UriFactory(),
            new ResponseFactory(),
            new JsonSerializer()
        );
    }
}

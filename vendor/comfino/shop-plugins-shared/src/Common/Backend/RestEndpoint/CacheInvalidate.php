<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\RestEndpoint;

use Comfino\Common\Backend\Cache\ItemTypeEnum;
use Comfino\Common\Backend\RestEndpoint;
use ComfinoExternal\Cache\TagInterop\TaggableCacheItemPoolInterface;
use ComfinoExternal\Psr\Cache\InvalidArgumentException;

class CacheInvalidate extends RestEndpoint
{
    /**
     * @var \ComfinoExternal\Cache\TagInterop\TaggableCacheItemPoolInterface
     */
    private $cache;
    public function __construct(
        string $name,
        string $endpointUrl,
        TaggableCacheItemPoolInterface $cache
    ) {
        $this->cache = $cache;
        parent::__construct($name, $endpointUrl);

        $this->methods = ['POST', 'PUT', 'PATCH'];
    }

    /**
     * @throws InvalidArgumentException
     * @param \ComfinoExternal\Psr\Http\Message\ServerRequestInterface $serverRequest
     * @param string|null $endpointName
     */
    public function processRequest($serverRequest, $endpointName = null): ?array
    {
        $this->cache->invalidateTags(
            array_intersect(
                parent::processRequest($serverRequest, $endpointName),
                ItemTypeEnum::values()
            )
        );

        return null;
    }
}

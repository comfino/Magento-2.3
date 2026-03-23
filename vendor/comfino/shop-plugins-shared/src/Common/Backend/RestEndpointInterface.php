<?php

declare(strict_types=1);

namespace Comfino\Common\Backend;

use Comfino\Api\SerializerInterface;
use Comfino\Common\Exception\InvalidEndpoint;
use Comfino\Common\Exception\InvalidRequest;
use ComfinoExternal\Psr\Http\Message\ServerRequestInterface;

interface RestEndpointInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string[]
     */
    public function getMethods(): array;

    /**
     * @return string
     */
    public function getEndpointUrl(): string;

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer($serializer): void;

    /**
     * @param ServerRequestInterface $serverRequest
     * @param string|null $endpointName
     * @return array|null
     * @throws InvalidEndpoint
     * @throws InvalidRequest
     */
    public function processRequest($serverRequest, $endpointName = null): ?array;
}

<?php

namespace Comperia\ComperiaGateway\Connector;

interface ResponseInterface
{
    public function __construct(string $status, RequestInterface $request, ?array $body);

    public function getBody(): array;

    public function getStatus(): string;
}

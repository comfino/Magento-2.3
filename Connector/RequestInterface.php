<?php

namespace Comperia\ComperiaGateway\Connector;

interface RequestInterface
{
    public function setBody(): string;

    public function getBody(): string;

    public function getParams(): array;
}

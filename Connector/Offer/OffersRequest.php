<?php

namespace Comperia\ComperiaGateway\Connector\Offer;

use Comperia\ComperiaGateway\Connector\RequestInterface;

class OffersRequest implements RequestInterface
{
    public const ENDPOINT = 'v1/financial-products';

    public function setBody(): string
    {

    }

    public function getBody(): string
    {
        return '';
    }

    public function getParams(): array
    {
        return [
            'loanAmount' => 100500,
            'loanTerm' => 10,
        ];
    }
}

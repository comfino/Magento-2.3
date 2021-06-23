<?php

namespace Comperia\ComperiaGateway\Connector\Offer;

use Comperia\ComperiaGateway\Connector\RequestInterface;
use Comperia\ComperiaGateway\Connector\ResponseInterface;

class OffersResponse implements  ResponseInterface
{
    /**
     * @var string
     */
    private $status;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var array|null
     */
    private $body;

    public function getBody(): array
    {
        return $this->body;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getOffersData(): array
    {
        return $this->body;
    }

    public function __construct(string $status, RequestInterface $request, ?array $body)
    {
        $this->status = $status;
        $this->request = $request;
        $this->body = $body;

        foreach ($this->body as $i => $offer) {
            $this->body[$i]['loanTerm'] = $this->request->getParams()['loanTerm'];
            $this->body[$i]['rrso'] = $offer['rrso'] * 100;
            $this->body[$i]['sumAmount'] = $offer['instalmentAmount'] * $offer['loanTerm'] / 100;
            $this->body[$i]['instalmentAmount'] = $offer['instalmentAmount'] / 100;
            $this->body[$i]['toPay'] = $offer['toPay'] / 100;
        }
    }
}

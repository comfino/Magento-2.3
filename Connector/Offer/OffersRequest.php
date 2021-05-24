<?php

namespace Comperia\ComperiaGateway\Connector\Offer;

use Comperia\ComperiaGateway\Connector\ApiConnector;
use Comperia\ComperiaGateway\Connector\RequestInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;

class OffersRequest implements RequestInterface
{
    public const ENDPOINT = 'v1/financial-products';

    /**
     * @var Session
     */
    private $session;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * OffersRequest constructor.
     *
     * @param Session $session
     */
    public function __construct(Session $session, ScopeConfigInterface $scopeConfig)
    {
        $this->session = $session;
        $this->scopeConfig = $scopeConfig;
    }

    public function setBody(): string
    {

    }

    public function getBody(): string
    {
        return '';
    }

    public function getParams(): array
    {
        $loanAmount = $this->session->getQuote()->getGrandTotal() * 100;

        return [
            'loanAmount' => $loanAmount,
            'loanTerm' => $this->scopeConfig->getValue(ApiConnector::LOAN_TERM),
        ];
    }
}

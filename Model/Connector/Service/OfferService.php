<?php

namespace Comperia\ComperiaGateway\Model\Connector\Service;

use Comperia\ComperiaGateway\Api\OfferServiceInterface;
use Comperia\ComperiaGateway\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use \Magento\Framework\Pricing\Helper\Data as PriceHelper;

class OfferService extends ServiceAbstract implements OfferServiceInterface
{
    const COMPERIA_API_OFFERS_URI = '/v1/financial-products';

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * OfferService constructor.
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param Data $helper
     * @param Session $session
     * @param ProductMetadataInterface $productMetadata
     * @param Request $request
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        Curl $curl,
        LoggerInterface
        $logger,
        SerializerInterface $serializer,
        Data $helper,
        Session $session,
        ProductMetadataInterface $productMetadata,
        Request $request,
        PriceHelper $priceHelper
    ) {
        parent::__construct($curl, $logger, $serializer, $helper, $session, $productMetadata, $request);
        $this->priceHelper = $priceHelper;
    }

    /**
     * Get offers from Comperia API.
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getList(): array
    {
        $apiUrl = $this->getApiUrl() . self::COMPERIA_API_OFFERS_URI;
        $loanAmount = $this->session->getQuote()->getGrandTotal() * 100;
        $params = ['loanAmount' => $loanAmount];

        $this->sendGetRequest($apiUrl, $params);

        return $this->getOffersResponse();
    }

    /**
     * @return array
     */
    private function getOffersResponse(): array
    {
        $body = $this->decode($this->curl->getBody());
        $offers = is_array($body) ? $body : [];

        foreach ($offers as &$offer) {
            $offer['icon'] = str_ireplace('<?xml version="1.0" encoding="UTF-8"?>', '', $offer['icon']);
            $offer['instalmentAmount'] = $this->getFormattedAmount($offer['instalmentAmount']);
            $offer['rrso'] *= 100;
            $offer['toPay'] = $this->getFormattedAmount($offer['toPay']);
            $offer['loanParameters'] = array_map(function ($loanParams) {
                return [
                    'loanTerm' => $loanParams['loanTerm'],
                    'instalmentAmount' => $this->getFormattedAmount($loanParams['instalmentAmount']),
                    'toPay' => $this->getFormattedAmount($loanParams['toPay']),
                ];
            }, $offer['loanParameters']);
        }

        return $offers;
    }

    /**
     * Reformat amount and add currency
     * @param $amount
     * @return string
     */
    private function getFormattedAmount($amount): string
    {
        return $this->priceHelper->currency($amount / 100, false, false);
    }
}

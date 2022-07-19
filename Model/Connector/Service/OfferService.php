<?php

namespace Comfino\ComfinoGateway\Model\Connector\Service;

use Comfino\ComfinoGateway\Api\OfferServiceInterface;
use Comfino\ComfinoGateway\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class OfferService extends ServiceAbstract implements OfferServiceInterface
{
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
     * Retrieves offers from Comfino API.
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getList(): array
    {
        $loanAmount = $this->session->getQuote()->getGrandTotal() * 100;

        if ($this->sendGetRequest($this->getApiUrl()."/v1/financial-products", ['loanAmount' => $loanAmount])) {
            return $this->getOffersResponse($loanAmount);
        }

        return [];
    }

    /**
     * Processes offer data from API response.
     *
     * @param float $total
     *
     * @return array
     */
    private function getOffersResponse(float $total): array
    {
        $body = $this->decode($this->curl->getBody());
        $offers = is_array($body) ? $body : [];
        $paymentOffers = [];

        foreach ($offers as $offer) {
            $paymentOffers[] = [
                'name' => $offer['name'],
                'description' => $offer['description'],
                'icon' => str_ireplace('<?xml version="1.0" encoding="UTF-8"?>', '', $offer['icon']),
                'type' => $offer['type'],
                'sumAmount' => $total / 100,
                'sumAmountFormatted' => $this->getFormattedAmount($total / 100),
                'representativeExample' => $offer['representativeExample'],
                'rrso' => ((float)$offer['rrso']) * 100,
                'loanTerm' => $offer['loanTerm'],
                'instalmentAmount' => ((float)$offer['instalmentAmount']) / 100,
                'instalmentAmountFormatted' => $this->getFormattedAmount(((float)$offer['instalmentAmount']) / 100),
                'toPay' => ((float)$offer['toPay']) / 100,
                'toPayFormatted' => $this->getFormattedAmount(((float)$offer['toPay']) / 100),
                'loanParameters' => array_map(function ($loanParams) use ($total) {
                    return [
                        'loanTerm' => $loanParams['loanTerm'],
                        'instalmentAmount' => ((float)$loanParams['instalmentAmount']) / 100,
                        'instalmentAmountFormatted' => $this->getFormattedAmount(
                            ((float)$loanParams['instalmentAmount']) / 100
                        ),
                        'toPay' => ((float)$loanParams['toPay']) / 100,
                        'toPayFormatted' => $this->getFormattedAmount(((float)$loanParams['toPay']) / 100),
                        'sumAmount' => $total / 100,
                        'sumAmountFormatted' => $this->getFormattedAmount($total / 100),
                        'rrso' => ((float)$loanParams['rrso']) * 100,
                    ];
                }, $offer['loanParameters'])
            ];
        }

        return $paymentOffers;
    }

    /**
     * Reformat amount and add currency.
     *
     * @param $amount
     * @return string
     */
    private function getFormattedAmount($amount): string
    {
        return $this->priceHelper->currency($amount / 100, false, false);
    }
}

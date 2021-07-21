<?php

namespace Comperia\ComperiaGateway\Connector\Transaction;

use Comperia\ComperiaGateway\Connector\ApiConnector;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Math\Random;
use Magento\Catalog\Model\Product;
use Comperia\ComperiaGateway\Controller\Notification\Index as NotificationController;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class ApplicationTransaction
 *
 * @package Comperia\ComperiaGateway\Connector\Transaction
 */
final class ApplicationTransaction extends Transaction
{
    const PATH = 'v1/orders';
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Random
     */
    private $random;
    /**
     * @var Image
     */
    private $imageHelper;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * ApplicationTransaction constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Random               $random
     * @param Image                $imageHelper
     * @param Session              $session
     * @param UrlInterface         $urlBuilder
     *
     * @throws LocalizedException
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Random $random,
        Image $imageHelper,
        Session $session,
        UrlInterface $urlBuilder,
        RemoteAddress $remoteAddress
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->random = $random;
        $this->imageHelper = $imageHelper;
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
        $this->remoteAddress = $remoteAddress;

        $this->setBody(json_encode($this->createApplicationData()));
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function createApplicationData(): array
    {
        /** @var Order $currentOrder */
        $order = $this->session->getLastRealOrder();

        $totalAmount = $order->getGrandTotal() * 100;

        $objectManager = ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        return [
            'returnUrl' => $storeManager->getStore()->getBaseUrl() . 'checkout/onepage/success',
            'orderId' => $order->getId(),
            'notifyUrl' => $storeManager->getStore()->getBaseUrl() . NotificationController::NOTIFICATION_URL,
            'loanParameters' => [
                'term' => (int)$this->scopeConfig->getValue(ApiConnector::LOAN_TERM),
            ],
            'cart' => [
                'totalAmount' => $totalAmount,
                'deliveryCost' => (int)$order->getBaseShippingAmount() * 100,
                'products' => $this->buildProductsList($order),
            ],
            'customer' => $this->buildCustomer($order),
        ];
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    private function buildProductsList(Order $order): array
    {
        $products = [];

        foreach ($order->getAllItems() as $item) {
            /** @var Product $product */
            $product = $item->getProduct();
            $imageHelper = $this->imageHelper->init($product, 'product_page_image_small');
            $image = $imageHelper->setImageFile($product->getImage())->getUrl();

            $products[] = [
                'name' => $item->getName(),
                'quantity' => (int)$item->getQtyOrdered(),
                'photoUrl' => $image,
                'ean' => null,
                'externalId' => $item->getId(),
                'price' => $item->getPriceInclTax() * 100,
            ];
        }

        return $products;
    }

    private function buildCustomer(Order $order): array
    {
        if ($order->getShippingAddress()) {
            /** @var Address $shippingAddress */
            $shippingAddress = $order->getShippingAddress()->getData();

            return [
                'firstName' => $shippingAddress['firstname'],
                'lastName' => $shippingAddress['lastname'],
                'ip' => $this->remoteAddress->getRemoteAddress(),
                'email' => $order->getCustomerEmail(),
                'phoneNumber' => $order->getShippingAddress()->getTelephone(),
                'address' => [
                    'street' => $shippingAddress['street'],
                    'postalCode' => $shippingAddress['postcode'],
                    'city' => $shippingAddress['city'],
                    'countryCode' => $shippingAddress['country_id'],
                ],
            ];
        }

        return [];
    }
}

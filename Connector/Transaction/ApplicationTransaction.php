<?php

namespace Comperia\ComperiaGateway\Connector\Transaction;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Math\Random;
use Magento\Catalog\Model\Product;
use Comperia\ComperiaGateway\Controller\Notification\Index as NotificationController;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Item;
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
        UrlInterface $urlBuilder
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->random = $random;
        $this->imageHelper = $imageHelper;
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
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
        /** @var Address $shippingAddress */
        $shippingAddress = $order->getShippingAddress()
            ->getData();

        $totalAmount = $order->getGrandTotal() * 100;

        //TODO get by DI?
        $objectManager = ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        return [
            'returnUrl' => $storeManager->getStore()->getBaseUrl() . 'checkout/onepage/success',
            'orderId' => $this->random->getUniqueHash(),
            'notifyUrl' => $storeManager->getStore()->getBaseUrl() . NotificationController::NOTIFICATION_URL,
            'loanParameters' => [
                'amount' => $totalAmount
            ],
            'cart' => [
                'totalAmount' => $totalAmount,
                'deliveryCost' => (int)$order->getBaseShippingAmount() * 100,
                'products' => $this->buildProductsList($order),
            ],
            'customer' => [
                'firstName' => $shippingAddress['firstname'],
                'lastName' => $shippingAddress['lastname'],
                'email' => $order->getCustomerEmail(),
                'phoneNumber' => $order->getShippingAddress()->getTelephone(),
                'address' => [
                    'street' => $shippingAddress['street'],
                    'postalCode' => $shippingAddress['postcode'],
                    'city' => $shippingAddress['city'],
                    'countryCode' => $shippingAddress['country_id'],
                ],
            ],
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
        /** @var Item $item */
        foreach ($order->getAllItems() as $item) {
            /** @var Product $product */
            $product = $item->getProduct();
            $imageHelper = $this->imageHelper->init($product, 'product_page_image_small');
            $image = $imageHelper->setImageFile($product->getImage())
                ->getUrl();
            $products[] = [
                'name' => $item->getName(),
                'quantity' => (int)$item->getQtyOrdered(),
                'photoUrl' => $image,
                'ean' => null,
                'externalId' => $item->getId(),
                'price' => $item->getPrice() * 100,
            ];
        }

        return $products;
    }
}

<?php

namespace Comperia\ComperiaGateway\Helper;

use Comperia\ComperiaGateway\Api\Data\ApplicationResponseInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Comperia\ComperiaGateway\Controller\Notification\Index as NotificationController;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;

class TransactionHelper extends AbstractHelper
{
    /**
     * @var Image
     */
    private $imageHelper;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var RemoteAddress
     */
    private $remoteAddress;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * TransactionHelper constructor.
     * @param Context $context
     * @param Image $imageHelper
     * @param Session $session
     * @param RemoteAddress $remoteAddress
     * @param SerializerInterface $serializer
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Context $context,
        Image $imageHelper,
        Session $session,
        RemoteAddress $remoteAddress,
        SerializerInterface $serializer,
        UrlInterface $urlBuilder
    ) {
        $this->imageHelper = $imageHelper;
        $this->session = $session;
        $this->remoteAddress = $remoteAddress;
        $this->urlBuilder = $urlBuilder;
        $this->serializer = $serializer;

        parent::__construct($context);
    }

    /**
     * Get JSON with transaction data
     * @return string
     */
    public function getTransactionData(): string
    {
        return $this->serializer->serialize($this->createTransactionData());
    }

    /**
     * Get Transaction Data for Comperia API request
     * @return array
     */
    private function createTransactionData(): array
    {
        /** @var Order $currentOrder */
        $order = $this->session->getLastRealOrder();
        $totalAmount = $order->getGrandTotal() * 100;
        $paymentInfo = $order->getPayment();

        return [
            'returnUrl' => $this->urlBuilder->getUrl('checkout/onepage/success'),
            'orderId' => $order->getId(),
            'notifyUrl' => $this->urlBuilder->getUrl(NotificationController::NOTIFICATION_URL),
            'loanParameters' => [
                'amount' => $totalAmount,
                'term' => (int)$paymentInfo->getAdditionalInformation('term'),
                'type' => $paymentInfo->getAdditionalInformation('type')
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

    /**
     * Build Customer Info
     * @param Order $order
     * @return array
     */
    private function buildCustomer(Order $order): array
    {
        if ($order->getShippingAddress()) {
            /** @var Address $shippingAddress */
            $shippingAddress = $order->getShippingAddress();

            return [
                'firstName' => $shippingAddress->getFirstname(),
                'lastName' => $shippingAddress->getLastname(),
                'ip' => $this->remoteAddress->getRemoteAddress(),
                'email' => $order->getCustomerEmail(),
                'phoneNumber' => $order->getShippingAddress()->getTelephone(),
                'address' => [
                    'street' => implode(', ', $shippingAddress->getStreet()),
                    'postalCode' => $shippingAddress->getPostcode(),
                    'city' => $shippingAddress->getCity(),
                    'countryCode' => $shippingAddress->getCountryId(),
                ],
            ];
        }

        return [];
    }

    /**
     * Parse data to Transaction model
     * @param ApplicationResponseInterface $model
     * @return array
     */
    public function parseModel(ApplicationResponseInterface $model): array
    {
        $data = $model->getData();
        $order = $this->session->getLastRealOrder();
        $data[ApplicationResponseInterface::ORDER_ID] = $order->getEntityId();
        return $data;
    }
}

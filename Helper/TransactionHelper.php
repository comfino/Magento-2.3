<?php

namespace Comfino\ComfinoGateway\Helper;

use Comfino\ComfinoGateway\Api\Data\ApplicationResponseInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Comfino\ComfinoGateway\Controller\Notification\Index as NotificationController;
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
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        Context $context,
        Image $imageHelper,
        Session $session,
        CustomerSession $customerSession,
        RemoteAddress $remoteAddress,
        SerializerInterface $serializer,
        UrlInterface $urlBuilder
    ) {
        $this->imageHelper = $imageHelper;
        $this->session = $session;
        $this->customerSession = $customerSession;
        $this->remoteAddress = $remoteAddress;
        $this->urlBuilder = $urlBuilder;
        $this->serializer = $serializer;

        parent::__construct($context);
    }

    /**
     * Get JSON with transaction data.
     *
     * @return string
     */
    public function getTransactionData(): string
    {
        return $this->serializer->serialize($this->createTransactionData());
    }

    /**
     * Returns transaction data for Comfino API request.
     *
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
                'loanTerm' => $item->getLoanTerm(),
            ];
        }

        return $products;
    }

    /**
     * Build Customer Info.
     *
     * @param Order $order
     *
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
                'phoneNumber' => $shippingAddress->getTelephone(),
                'logged' => $this->customerSession->isLoggedIn(),
                'address' => [
                    'street' => implode(', ', $shippingAddress->getStreet()),
                    'postalCode' => $shippingAddress->getPostcode(),
                    'city' => $shippingAddress->getCity(),
                    'countryCode' => $shippingAddress->getCountryId(),
                ],
            ];
        }

        if ($order->getBillingAddress()) {
            /** @var Address $billingAddress */
            $billingAddress = $order->getBillingAddress();

            return [
                'firstName' => $billingAddress->getFirstname(),
                'lastName' => $billingAddress->getLastname(),
                'ip' => $this->remoteAddress->getRemoteAddress(),
                'email' => $order->getCustomerEmail(),
                'phoneNumber' => $billingAddress->getTelephone(),
                'logged' => $this->customerSession->isLoggedIn(),
                'address' => [
                    'street' => implode(', ', $billingAddress->getStreet()),
                    'postalCode' => $billingAddress->getPostcode(),
                    'city' => $billingAddress->getCity(),
                    'countryCode' => $billingAddress->getCountryId(),
                ],
            ];
        }

        return [];
    }

    /**
     * Parse data to Transaction model.
     */
    public function parseModel(ApplicationResponseInterface $model): array
    {
        return array_merge($model->getData(), [ApplicationResponseInterface::ORDER_ID => $this->session->getLastRealOrder()->getEntityId()]);
    }
}

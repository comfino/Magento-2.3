<?php

namespace Comfino\ComfinoGateway\Model\Connector\Service;

use Comfino\Api\ApiClient;
use Comfino\Api\Dto\Payment\LoanTypeEnum;
use Comfino\Api\Response\CreateOrder;
use Comfino\ComfinoGateway\Api\ApplicationServiceInterface;
use Comfino\Common\Backend\Factory\OrderFactory;
use Comfino\Configuration\ConfigManager;
use Comfino\DebugLogger;
use Comfino\ErrorLogger;
use Comfino\FinancialProduct\ProductTypesListTypeEnum;
use Comfino\Order\OrderManager;
use Comfino\Order\ShopStatusManager;
use Comfino\Shop\Order\Cart\CartItem;
use Comfino\Shop\Order\Cart\Product;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class ApplicationService implements ApplicationServiceInterface
{
    private Session $session;
    private OrderRepository $orderRepository;
    private UrlInterface $urlBuilder;
    private RemoteAddress $remoteAddress;
    private CustomerSession $customerSession;

    public function __construct(
        Session $session,
        OrderRepository $orderRepository,
        UrlInterface $urlBuilder,
        RemoteAddress $remoteAddress,
        CustomerSession $customerSession
    ) {
        $this->session = $session;
        $this->orderRepository = $orderRepository;
        $this->urlBuilder = $urlBuilder;
        $this->remoteAddress = $remoteAddress;
        $this->customerSession = $customerSession;

        ErrorLogger::init();
    }

    /**
     * Creates application in the Comfino API and returns the redirect URL.
     */
    public function save(): array
    {
        try {
            $response = $this->createApplicationTransaction();
        } catch (\InvalidArgumentException $e) {
            // Local or API validation failure - set failure status but do not report to Comfino error tracker.
            $this->setOrderFailureStatus();

            return [[
                'redirectUrl' => $this->urlBuilder->getUrl('checkout/onepage/failure'),
                'error' => $e->getMessage(),
            ]];
        } catch (\Throwable $e) {
            $this->setOrderFailureStatus();

            ApiClient::processApiError('Communication error with Comfino API', $e);

            return [[
                'redirectUrl' => $this->urlBuilder->getUrl('checkout/onepage/failure'),
                'error' => (string) __('Unsuccessful attempt to open the application. Please try again later.'),
            ]];
        }

        DebugLogger::logEvent('ApplicationService', 'Redirect URL: ' . $response->applicationUrl);

        return [['redirectUrl' => $response->applicationUrl]];
    }

    /**
     * Sends a cancellation request to the Comfino API.
     */
    public function cancelApplicationTransaction(string $orderId): void
    {
        DebugLogger::logEvent('[APPLICATION_SERVICE]', "cancelApplicationTransaction: Cancelling order $orderId.");

        try {
            // Send notification about canceled order paid by Comfino.
            ApiClient::getInstance()->cancelOrder($orderId);

            DebugLogger::logEvent('[APPLICATION_SERVICE]', "cancelApplicationTransaction: Order $orderId cancelled successfully.");
        } catch (\Throwable $e) {
            ApiClient::processApiError('Cancel order error', $e);
        }
    }

    /**
     * Returns widget key received from Comfino API.
     */
    public function getWidgetKey(): string
    {
        try {
            return ApiClient::getInstance()->getWidgetKey();
        } catch (\Throwable $e) {
            ApiClient::processApiError('Get widget key error', $e);

            return '';
        }
    }

    /**
     * Returns list of available product types for Comfino widget.
     */
    public function getProductTypes(): ?array
    {
        try {
            $response = ApiClient::getInstance()->getProductTypes(
                ProductTypesListTypeEnum::from(ProductTypesListTypeEnum::LIST_TYPE_WIDGET)
            );

            return $response->productTypesWithNames;
        } catch (\Throwable $e) {
            ApiClient::processApiError('Get product types error', $e);

            return null;
        }
    }

    /**
     * Returns true if the shop account is active in Comfino API.
     */
    public function isShopAccountActive(): bool
    {
        if (empty(ConfigManager::getApiKey())) {
            return false;
        }

        try {
            return ApiClient::getInstance()->isShopAccountActive();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Returns logo URL from Comfino API.
     */
    public function getLogoUrl(): string
    {
        return ConfigManager::getApiHost(ApiClient::getInstance()->getApiHost()) . '/v1/get-logo-url';
    }

    /**
     * Validates and creates a Comfino order via the shared library API client.
     *
     * Performs local data validation, then Comfino API validation (simulation), then creates the order.
     * The Magento order entity_id is used as the external order identifier passed to Comfino.
     *
     * @return CreateOrder
     *
     * @throws \InvalidArgumentException On local or API validation failure.
     * @throws \Throwable On API communication error.
     */
    private function createApplicationTransaction(): CreateOrder
    {
        $magentoOrder = $this->session->getLastRealOrder();
        $orderDto     = $this->buildOrderDto($magentoOrder);

        // Step 1: Local pre-validation.
        $errors = $this->validatePaymentData($orderDto);

        if (!empty($errors)) {
            DebugLogger::logEvent('[PAYMENT]', 'Local validation failed.', ['errors' => $errors]);

            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        // Step 2: API-side validation (simulation=true, no order created yet).
        $validationResult = ApiClient::getInstance()->validateOrder($orderDto);

        if (!$validationResult->success) {
            $apiErrors = array_values((array) $validationResult->errors);

            DebugLogger::logEvent('[PAYMENT]', 'API validation failed.', ['errors' => $apiErrors]);

            throw new \InvalidArgumentException(implode(' ', $apiErrors));
        }

        // Step 3: Create order.
        $response = ApiClient::getInstance()->createOrder($orderDto);

        // Step 4: Mark order with the configured initial Comfino status.
        $this->setComfinoCreatedStatus($magentoOrder);

        return $response;
    }

    /**
     * Builds the shared-lib Order DTO from the given Magento order.
     *
     * When COMFINO_USE_ORDER_REFERENCE is enabled the increment_id (customer-visible order number, e.g. "100000001")
     * is used as the external order identifier passed to Comfino instead of the internal entity_id.
     *
     * @param \Magento\Sales\Model\Order $magentoOrder
     * @return \Comfino\Shop\Order\Order
     */
    private function buildOrderDto(\Magento\Sales\Model\Order $magentoOrder): \Comfino\Shop\Order\Order
    {
        $totalAmount  = (int) round($magentoOrder->getGrandTotal() * 100);
        $deliveryCost = (int) round((float) $magentoOrder->getBaseShippingInclTax() * 100);
        $paymentInfo  = $magentoOrder->getPayment();
        $loanTerm     = (int) $paymentInfo->getAdditionalInformation('loanTerm');
        $loanType     = (string) $paymentInfo->getAdditionalInformation('loanType');

        $cartItems = [];

        foreach ($magentoOrder->getAllItems() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            $product    = $item->getProduct();
            $grossPrice = (int) round((float) $item->getPriceInclTax() * 100);
            $netPrice   = (int) round((float) $item->getPrice() * 100);
            $quantity   = (int) $item->getQtyOrdered();

            $cartItems[] = new CartItem(
                new Product(
                    (string) $item->getName(),
                    $grossPrice,
                    $product ? (string) $product->getId() : null,
                    null,  // category
                    null,  // ean
                    null,  // photoUrl
                    null,  // categoryIds
                    $netPrice,
                    null,  // taxRate
                    $grossPrice - $netPrice // taxValue
                ),
                $quantity
            );
        }

        $customer = OrderManager::getShopCustomerFromOrder(
            $magentoOrder,
            (string) $this->remoteAddress->getRemoteAddress(),
            $this->customerSession->isLoggedIn()
        );

        $externalId = ConfigManager::isUseOrderReference()
            ? (!empty($magentoOrder->getIncrementId()) ? $magentoOrder->getIncrementId() : (string) $magentoOrder->getId())
            : (string) $magentoOrder->getId();

        return (new OrderFactory())->createOrder(
            $externalId,
            $totalAmount,
            $deliveryCost,
            $loanTerm,
            LoanTypeEnum::from($loanType),
            $cartItems,
            $customer,
            rtrim($this->urlBuilder->getUrl('checkout/onepage/success'), '/'),
            rtrim($this->urlBuilder->getUrl('comfino/transactionstatus'), '/')
        );
    }

    /**
     * Validates payment data from the Order DTO before submission to Comfino API.
     *
     * @param \Comfino\Shop\Order\Order $orderDto
     * @return string[] Array of error messages; empty if validation passes.
     */
    private function validatePaymentData(\Comfino\Shop\Order\Order $orderDto): array
    {
        $errors   = [];
        $customer = $orderDto->getCustomer();

        // 1. Customer e-mail.
        $email = $customer !== null ? $customer->getEmail() : '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = (string) __('Invalid customer e-mail address. Please check your account contact data.');
        }

        // 2. Phone number.
        if ($customer === null || empty($customer->getPhoneNumber())) {
            $errors[] = (string) __('Phone number is required. Please add a phone number to your billing or delivery address.');
        }

        // 3. Customer names.
        if ($customer === null || empty(trim($customer->getFirstName()))) {
            $errors[] = (string) __('First name is required.');
        }

        if ($customer === null || empty(trim($customer->getLastName()))) {
            $errors[] = (string) __('Last name is required.');
        }

        // 4. Delivery address.
        $address = $customer !== null ? $customer->getAddress() : null;

        if ($address === null) {
            $errors[] = (string) __('Delivery address is required.');
        } else {
            if (empty(trim($address->getCity()))) {
                $errors[] = (string) __('City is required.');
            }

            if (empty(trim($address->getPostalCode()))) {
                $errors[] = (string) __('Postal code is required.');
            }
        }

        // 5. Cart items.
        if (empty($orderDto->getCart()->getItems())) {
            $errors[] = (string) __('Cart is empty. Please add products to your cart.');
        }

        // 6. Total amount.
        if ($orderDto->getCart()->getTotalAmount() <= 0) {
            $errors[] = (string) __('Cart total amount must be greater than zero.');
        }

        return $errors;
    }

    /**
     * Marks the order with the configured initial Comfino order status after successful API submission.
     * Uses COMFINO_INITIAL_ORDER_STATUS config value; defaults to comfino_created.
     *
     * @param \Magento\Sales\Model\Order $order
     */
    private function setComfinoCreatedStatus(\Magento\Sales\Model\Order $order): void
    {
        try {
            $initialStatus = ConfigManager::getInitialOrderStatus();
            $initialState  = ShopStatusManager::CUSTOM_STATUS_LABELS[$initialStatus]['state']
                ?? Order::STATE_PENDING_PAYMENT;

            $order->setState($initialState)->setStatus($initialStatus);
            $order->addStatusToHistory(
                $initialStatus,
                __('Order submitted to Comfino - waiting for payment.')
            );
            $this->orderRepository->save($order);
        } catch (\Throwable $e) {
            ErrorLogger::sendError($e, 'Comfino created status update error', (string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Sets the order to pending_payment state on application creation failure.
     */
    private function setOrderFailureStatus(): void
    {
        try {
            $order = $this->session->getLastRealOrder();
            $order->setStatus(Order::STATE_PENDING_PAYMENT)->setState(Order::STATE_PENDING_PAYMENT);
            $order->addStatusToHistory(
                Order::STATE_PENDING_PAYMENT,
                __('Unsuccessful attempt to open the application. Communication error with Comfino API.')
            );
            $this->orderRepository->save($order);
        } catch (\Throwable $e) {
            ErrorLogger::sendError($e, 'Order failure status update error', (string) $e->getCode(), $e->getMessage());
        }
    }
}

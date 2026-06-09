<?php

namespace Comfino\ComfinoGateway\Model\Connector\Service;

use Comfino\Api\ApiClient;
use Comfino\Api\Dto\Payment\LoanTypeEnum;
use Comfino\Api\Exception\RequestValidationError;
use Comfino\Api\Response\CreateOrder;
use Comfino\ComfinoGateway\Api\ApplicationServiceInterface;
use Comfino\Common\Backend\Factory\OrderFactory;
use Comfino\Configuration\ConfigManager;
use Comfino\Configuration\SettingsManager;
use Comfino\DebugLogger;
use Comfino\ErrorLogger;
use Comfino\FinancialProduct\ProductTypesListTypeEnum;
use Comfino\Order\OrderManager;
use Comfino\Order\ShopStatusManager;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
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
     * Creates an application in the Comfino API and returns the redirect URL.
     */
    public function save(): array
    {
        try {
            $response = $this->createApplicationTransaction();
        } catch (\InvalidArgumentException $e) {
            /* Local or API validation failure - keep the cart and report the message to the customer.
               Not reported to the Comfino error tracker (expected validation outcome, not a fault). */
            $this->restoreCartAfterFailure($e->getMessage());

            return [['error' => $e->getMessage()]];
        } catch (RequestValidationError $e) {
            /* HTTP 400 from createOrder() - the API rejected the request payload.
               Treat the same as local validation: show the real API error, skip the error tracker. */
            $this->restoreCartAfterFailure($e->getMessage());

            return [['error' => $e->getMessage()]];
        } catch (\Throwable $e) {
            ApiClient::processApiError('Communication error with Comfino API', $e);

            $errorMessage = (string) __('Unsuccessful attempt to open the application. Please try again later.');

            $this->restoreCartAfterFailure($errorMessage);

            return [['error' => $errorMessage]];
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
     * Returns the list of available product types for Comfino widget.
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
        $orderDto = $this->buildOrderDto($magentoOrder);

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
     * When COMFINO_USE_ORDER_REFERENCE is enabled, the increment_id (customer-visible order number, e.g. "100000001")
     * is used as the external order identifier passed to Comfino instead of the internal entity_id.
     *
     * @param Order $magentoOrder
     *
     * @return \Comfino\Shop\Order\Order
     * @throws LocalizedException
     */
    private function buildOrderDto(Order $magentoOrder): \Comfino\Shop\Order\Order
    {
        /* Build the Comfino cart from the persisted order (the source quote is no longer reliable after placement).
           This provides the full cart items (category path, EAN, image, per-item tax) and the delivery cost breakdown
           (net cost, tax rate, tax value) in a single, currency-consistent place. */
        $orderCart = OrderManager::getShopCartFromOrder($magentoOrder);

        $paymentInfo = $magentoOrder->getPayment();
        $loanTerm = (int) $paymentInfo->getAdditionalInformation('loanTerm');
        $loanType = (string) $paymentInfo->getAdditionalInformation('loanType');

        $customer = OrderManager::getShopCustomerFromOrder(
            $magentoOrder,
            (string) $this->remoteAddress->getRemoteAddress(),
            $this->customerSession->isLoggedIn()
        );

        $externalId = ConfigManager::isUseOrderReference()
            ? (!empty($magentoOrder->getIncrementId()) ? $magentoOrder->getIncrementId() : (string) $magentoOrder->getId())
            : (string) $magentoOrder->getId();

        $allowedProductTypes = null;

        try {
            $allowedProductTypes = SettingsManager::getAllowedProductTypes(
                ProductTypesListTypeEnum::LIST_TYPE_PAYWALL,
                $orderCart
            );
        } catch (\Throwable $e) {
            // Ignore - proceed without product type filter
        }

        return (new OrderFactory())->createOrder(
            $externalId,
            $orderCart->getTotalValue(),
            $orderCart->getDeliveryCost(),
            $loanTerm,
            LoanTypeEnum::from($loanType),
            $orderCart->getCartItems(),
            $customer,
            rtrim($this->urlBuilder->getUrl('checkout/onepage/success'), '/'),
            rtrim($this->urlBuilder->getUrl('comfino/transactionstatus'), '/'),
            $allowedProductTypes,
            $orderCart->getDeliveryNetCost(),
            $orderCart->getDeliveryTaxRate(),
            $orderCart->getDeliveryTaxValue()
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
        $email = $customer->getEmail();

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = (string) __('Invalid customer e-mail address. Please check your account contact data.');
        }

        // 2. Phone number.
        if (empty($customer->getPhoneNumber())) {
            $errors[] = (string) __('Phone number is required. Please add a phone number to your billing or delivery address.');
        }

        // 3. Customer names.
        if (empty(trim($customer->getFirstName()))) {
            $errors[] = (string) __('First name is required.');
        }

        if (empty(trim($customer->getLastName()))) {
            $errors[] = (string) __('Last name is required.');
        }

        // 4. Delivery address.
        $address = $customer->getAddress();

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
     * @param Order $order
     */
    private function setComfinoCreatedStatus(Order $order): void
    {
        try {
            $initialStatus = ConfigManager::getInitialOrderStatus();
            $initialState = ShopStatusManager::CUSTOM_STATUS_LABELS[$initialStatus]['state']
                ?? Order::STATE_PENDING_PAYMENT;

            /* Flag persisted with the order so the cancel observer can distinguish orders that were
               actually submitted to Comfino from orphaned orders canceled by restoreCartAfterFailure(). */
            $order->getPayment()->setAdditionalInformation('comfino_order_created', true);
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
     * Restores the customer's cart after an application creation failure and cancels the orphaned order.
     *
     * In Magento the order is placed (and the source quote deactivated) by the standard checkout flow before this
     * service runs, so a failure here would otherwise leave the customer with an empty cart on a generic failure page.
     * Mirroring the PrestaShop/WooCommerce behavior, the cart contents are preserved and the customer is shown the
     * error message instead:
     *
     *  - The orphaned Magento order is canceled (releases reserved stock; no Comfino order was created);
     *  - The source quote is reactivated via {@see Session::restoreQuote()} so the cart can be retried.
     *
     * @param string $reason Human-readable failure reason recorded in the order status history.
     */
    private function restoreCartAfterFailure(string $reason): void
    {
        try {
            // Cancel the orphaned order (placed before the Comfino application) to release reserved stock.
            $order = $this->session->getLastRealOrder();

            if ($order->getId() && $order->canCancel()) {
                $order->cancel();
                $order->addStatusToHistory(
                    $order->getStatus(),
                    (string) __('Comfino application creation failed: %1', $reason)
                );
                $this->orderRepository->save($order);
            }
        } catch (\Throwable $e) {
            ErrorLogger::sendError($e, 'Order cancellation error', (string) $e->getCode(), $e->getMessage());
        }

        try {
            // Reactivate the source quote so the customer keeps the cart contents and can retry the payment.
            $this->session->restoreQuote();
        } catch (\Throwable $e) {
            ErrorLogger::sendError($e, 'Cart restore error', (string) $e->getCode(), $e->getMessage());
        }
    }
}

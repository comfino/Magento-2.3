<?php

declare(strict_types=1);

namespace Comfino\ComfinoGatewayHyvaCheckout\Model\Checkout;

use Comfino\ComfinoGateway\Api\ApplicationServiceInterface;
use Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;

/**
 * Handles Comfino order placement in Hyvä Checkout.
 *
 * Registered in PlaceOrderServiceProvider (etc/frontend/di.xml). Hyvä Checkout
 * calls getRedirectUrl() after the Magento order has been placed via
 * CartManagementInterface (handled by the base class). At that point the payment
 * additional_information already contains loanType and loanTerm (written by
 * DataAssignObserver when the customer confirmed an offer in the Comfino paywall).
 * ApplicationService::save() reads those values via CheckoutSession::getLastRealOrder(),
 * creates the Comfino financing application, and returns its redirect URL.
 */
class PlaceOrderService extends AbstractPlaceOrderService
{
    private ApplicationServiceInterface $applicationService;

    public function __construct(
        CartManagementInterface $cartManagement,
        ApplicationServiceInterface $applicationService
    ) {
        parent::__construct($cartManagement);
        $this->applicationService = $applicationService;
    }

    public function canPlaceOrder(): bool
    {
        return true;
    }

    public function canRedirect(): bool
    {
        return true;
    }

    /**
     * Called by Hyvä Checkout after the Magento order has been placed.
     * Returns the Comfino application URL to redirect the customer to.
     *
     * @throws LocalizedException When ApplicationService returns no redirect URL.
     */
    public function getRedirectUrl(Quote $quote, ?int $orderId = null): string
    {
        $result      = $this->applicationService->save();
        $redirectUrl = $result[0]['redirectUrl'] ?? null;

        if (empty($redirectUrl)) {
            throw new LocalizedException(
                __('Comfino payment failed: no redirect URL returned.')
            );
        }

        return $redirectUrl;
    }
}
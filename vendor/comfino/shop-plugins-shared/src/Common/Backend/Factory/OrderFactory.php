<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Factory;

use Comfino\Api\Dto\Payment\LoanTypeEnum;
use Comfino\Shop\Order\Cart;
use Comfino\Shop\Order\Cart\CartItemInterface;
use Comfino\Shop\Order\CustomerInterface;
use Comfino\Shop\Order\LoanParameters;
use Comfino\Shop\Order\Order;

final class OrderFactory
{
    /**
     * @param CartItemInterface[] $cartItems
     * @param LoanTypeEnum[]|null $allowedProductTypes
     */
    public function createOrder(
        string $orderId,
        int $orderTotal,
        int $deliveryCost,
        int $loanTerm,
        LoanTypeEnum $loanType,
        array $cartItems,
        CustomerInterface $customer,
        string $returnUrl,
        string $notificationUrl,
        ?array $allowedProductTypes = null,
        ?int $deliveryNetCost = null,
        ?int $deliveryCostTaxRate = null,
        ?int $deliveryCostTaxValue = null,
        ?string $category = null
    ): Order {
        return new Order(
            $orderId,
            $returnUrl,
            new LoanParameters(
                $orderTotal,
                $loanTerm,
                $loanType,
                $allowedProductTypes
            ),
            new Cart(
                $cartItems,
                $orderTotal,
                $deliveryCost,
                $deliveryNetCost,
                $deliveryCostTaxRate,
                $deliveryCostTaxValue,
                $category
            ),
            $customer,
            $notificationUrl
        );
    }
}

<?php

declare(strict_types=1);

namespace Comfino\Api\Response;

use Comfino\Api\Dto\Order\Cart;
use Comfino\Api\Dto\Order\Customer;
use Comfino\Api\Dto\Order\LoanParameters;
use Comfino\Api\Dto\Payment\LoanTypeEnum;

class GetOrder extends Base
{
    public $orderId;

    public $status;

    public $createdAt;

    public $applicationUrl;

    public $notifyUrl;

    public $returnUrl;

    public $loanParameters;

    public $cart;

    public $customer;

    /**
     * @param mixed[]|string|bool|null|float|int $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
        $this->checkResponseType($deserializedResponseBody, 'array');
        $this->checkResponseStructure(
            $deserializedResponseBody,
            ['orderId', 'status', 'createdAt', 'applicationUrl', 'notifyUrl', 'returnUrl', 'loanParameters', 'cart', 'customer']
        );
        $this->checkResponseType($deserializedResponseBody['loanParameters'], 'array', 'loanParameters');
        $this->checkResponseType($deserializedResponseBody['cart'], 'array', 'cart');
        $this->checkResponseType($deserializedResponseBody['customer'], 'array', 'customer');

        try {
            $createdAt = new \DateTime($deserializedResponseBody['createdAt']);
        } catch (\Exception $exception) {
            $createdAt = null;
        }

        $this->orderId = $deserializedResponseBody['orderId'];
        $this->status = $deserializedResponseBody['status'];
        $this->createdAt = $createdAt;
        $this->applicationUrl = $deserializedResponseBody['applicationUrl'];
        $this->notifyUrl = $deserializedResponseBody['notifyUrl'];
        $this->returnUrl = $deserializedResponseBody['returnUrl'];

        $this->checkResponseStructure(
            $deserializedResponseBody['loanParameters'],
            ['amount', 'maxAmount', 'term', 'type', 'allowedProductTypes']
        );

        $this->loanParameters = new LoanParameters(
            $deserializedResponseBody['loanParameters']['amount'],
            $deserializedResponseBody['loanParameters']['maxAmount'],
            $deserializedResponseBody['loanParameters']['term'],
            LoanTypeEnum::from($deserializedResponseBody['loanParameters']['type']),
            $deserializedResponseBody['loanParameters']['allowedProductTypes'] !== null ? array_map(
                static function (string $productType): LoanTypeEnum {
                    return LoanTypeEnum::from($productType);
                },
                $deserializedResponseBody['loanParameters']['allowedProductTypes']
            ) : null
        );

        $this->checkResponseStructure(
            $deserializedResponseBody['cart'],
            ['totalAmount', 'deliveryCost', 'category', 'products']
        );
        $this->checkResponseType($deserializedResponseBody['cart']['products'], 'array', 'cart.products');

        $this->cart = new Cart(
            $deserializedResponseBody['cart']['totalAmount'],
            $deserializedResponseBody['cart']['deliveryCost'],
            $deserializedResponseBody['cart']['category'],
            array_map(
                function ($cartItem): Cart\CartItem {
                    $this->checkResponseType($cartItem, 'array');
                    $this->checkResponseStructure(
                        $cartItem,
                        ['name', 'price', 'quantity', 'externalId', 'photoUrl', 'ean', 'category']
                    );

                    return new Cart\CartItem(
                        $cartItem['name'],
                        $cartItem['price'],
                        $cartItem['quantity'],
                        $cartItem['externalId'],
                        $cartItem['photoUrl'],
                        $cartItem['ean'],
                        $cartItem['category'],
                        $cartItem['netPrice'] ?? null,
                        $cartItem['vatRate'] ?? null,
                        $cartItem['vatAmount'] ?? null
                    );
                },
                $deserializedResponseBody['cart']['products']
            )
        );

        $this->checkResponseStructure(
            $deserializedResponseBody['customer'],
            ['firstName', 'lastName', 'email', 'phoneNumber', 'ip', 'taxId', 'regular', 'logged', 'address']
        );

        if (is_array($deserializedResponseBody['customer']['address'])) {
            $this->checkResponseStructure(
                $deserializedResponseBody['customer']['address'],
                ['street', 'buildingNumber', 'apartmentNumber', 'postalCode', 'city', 'countryCode']
            );
        }

        $this->customer = new Customer(
            $deserializedResponseBody['customer']['firstName'],
            $deserializedResponseBody['customer']['lastName'],
            $deserializedResponseBody['customer']['email'],
            $deserializedResponseBody['customer']['phoneNumber'],
            $deserializedResponseBody['customer']['ip'],
            $deserializedResponseBody['customer']['taxId'],
            $deserializedResponseBody['customer']['regular'],
            $deserializedResponseBody['customer']['logged'],
            $deserializedResponseBody['customer']['address'] !== null ? new Customer\Address(
                $deserializedResponseBody['customer']['address']['street'],
                $deserializedResponseBody['customer']['address']['buildingNumber'],
                $deserializedResponseBody['customer']['address']['apartmentNumber'],
                $deserializedResponseBody['customer']['address']['postalCode'],
                $deserializedResponseBody['customer']['address']['city'],
                $deserializedResponseBody['customer']['address']['countryCode']
            ) : null
        );
    }
}

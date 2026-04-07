<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Request;
use Comfino\Shop\Order\CartTrait;
use Comfino\Shop\Order\OrderInterface;

class CreateOrder extends Request
{
    use CartTrait;
    /**
     * @var OrderInterface
     */
    private $order;
    /**
     * @var bool
     */
    private $validateOnly = false;

    /**
     * @var mixed[]|null
     */
    private $preparedRequestBody;

    /**
     * @param OrderInterface $order
     * @param string $apiKey
     * @param bool $validateOnly
     */
    public function __construct(OrderInterface $order, string $apiKey, bool $validateOnly = false)
    {
        $this->order = $order;
        $this->validateOnly = $validateOnly;
        $this->setRequestMethod('POST');
        $this->setApiEndpointPath('orders');

        $preparedRequestBody = $this->prepareRequestBody();
        $cartHash = $this->generateHash($preparedRequestBody['cart']);
        $customerHash = $this->generateHash($preparedRequestBody['customer']);

        $this->setRequestHeaders([
            'Comfino-Cart-Hash' => $cartHash,
            'Comfino-Customer-Hash' => $customerHash,
            'Comfino-Order-Signature' => hash('sha3-256', $cartHash . $customerHash . $apiKey),
        ]);
    }

    protected function prepareRequestBody(): array
    {
        if ($this->preparedRequestBody !== null) {
            return $this->preparedRequestBody;
        }

        $customer = $this->order->getCustomer();

        $this->preparedRequestBody = array_filter(
            [

                'notifyUrl' => $this->order->getNotifyUrl(),
                'returnUrl' => $this->order->getReturnUrl(),
                'orderId' => $this->order->getId(),

                'loanParameters' => array_filter(
                    [
                        'amount' => $this->order->getLoanParameters()->getAmount(),
                        'term' => $this->order->getLoanParameters()->getTerm(),
                        'type' => $this->order->getLoanParameters()->getType(),
                        'allowedProductTypes' => $this->order->getLoanParameters()->getAllowedProductTypes(),
                    ],
                    static function ($value): bool {
                        return $value !== null;
                    }
                ),

                'cart' => $this->getCartAsArray($this->order->getCart()),

                'customer' => array_filter(
                    [
                        'firstName' => $customer->getFirstName(),
                        'lastName' => $customer->getLastName(),
                        'email' => $customer->getEmail(),
                        'phoneNumber' => $customer->getPhoneNumber(),
                        'taxId' => $customer->getTaxId(),
                        'ip' => $customer->getIp(),
                        'regular' => $customer->isRegular(),
                        'logged' => $customer->isLogged(),

                        'address' => count(
                            $address = array_filter(
                                [
                                    'street' => ($nullsafeVariable1 = $customer->getAddress()) ? $nullsafeVariable1->getStreet() : null,
                                    'buildingNumber' => ($nullsafeVariable2 = $customer->getAddress()) ? $nullsafeVariable2->getBuildingNumber() : null,
                                    'apartmentNumber' => ($nullsafeVariable3 = $customer->getAddress()) ? $nullsafeVariable3->getApartmentNumber() : null,
                                    'postalCode' => ($nullsafeVariable4 = $customer->getAddress()) ? $nullsafeVariable4->getPostalCode() : null,
                                    'city' => ($nullsafeVariable5 = $customer->getAddress()) ? $nullsafeVariable5->getCity() : null,
                                    'countryCode' => ($nullsafeVariable6 = $customer->getAddress()) ? $nullsafeVariable6->getCountryCode() : null,
                                ],
                                static function ($value): bool {
                                    return $value !== null;
                                }
                            )
                        ) ? $address : null,
                    ],
                    static function ($value): bool {
                        return $value !== null;
                    }
                ),

                'seller' => count(
                    $seller = array_filter(
                        ['taxId' => ($nullsafeVariable7 = $this->order->getSeller()) ? $nullsafeVariable7->getTaxId() : null],
                        static function ($value): bool {
                            return $value !== null;
                        }
                    )
                ) ? $seller : null,

                'accountNumber' => $this->order->getAccountNumber(),
                'transferTitle' => $this->order->getTransferTitle(),
                'simulation' => $this->validateOnly ?: null,
            ],
            static function ($value): bool {
                return $value !== null;
            }
        );

        return $this->preparedRequestBody;
    }

    private function generateHash(array $data): string
    {
        try {
            return hash('sha3-256', json_encode($data, JSON_PRESERVE_ZERO_FRACTION));
        } catch (\JsonException $exception) {
            return '';
        }
    }
}

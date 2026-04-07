<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Dto\Payment\LoanTypeEnum;
use Comfino\Api\Request;
use Comfino\Shop\Order\CartInterface;
use Comfino\Shop\Order\CartTrait;

class GetPaywallItemDetails extends Request
{
    use CartTrait;
    /**
     * @var CartInterface
     */
    private $cart;

    /**
     * @param int $loanAmount
     * @param LoanTypeEnum $loanType
     * @param CartInterface $cart
     */
    public function __construct(int $loanAmount, LoanTypeEnum $loanType, CartInterface $cart)
    {
        $this->cart = $cart;
        $this->setRequestMethod('POST');
        $this->setApiEndpointPath('shop-plugin-paywall-product-details');
        $this->setRequestParams(['loanAmount' => $loanAmount, 'loanTypeSelected' => (string) $loanType]);
    }

    protected function prepareRequestBody(): ?array
    {
        return $this->getCartAsArray($this->cart);
    }
}

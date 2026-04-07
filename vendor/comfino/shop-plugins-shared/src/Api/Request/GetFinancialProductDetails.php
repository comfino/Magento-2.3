<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Dto\Payment\LoanQueryCriteria;
use Comfino\Api\Request;
use Comfino\Shop\Order\CartInterface;
use Comfino\Shop\Order\CartTrait;

class GetFinancialProductDetails extends Request
{
    use CartTrait;
    /**
     * @var CartInterface
     */
    private $cart;

    /**
     * @param LoanQueryCriteria $queryCriteria
     * @param CartInterface $cart
     */
    public function __construct(LoanQueryCriteria $queryCriteria, CartInterface $cart)
    {
        $this->cart = $cart;
        $this->setRequestMethod('POST');
        $this->setApiEndpointPath('financial-products');
        $this->setRequestParams(
            array_filter(
                [
                    'loanAmount' => $queryCriteria->loanAmount,
                    'loanTerm' => $queryCriteria->loanTerm,
                    'loanTypeSelected' => $queryCriteria->loanType,
                    'productTypes' => ($queryCriteria->productTypes !== null ? implode(',', $queryCriteria->productTypes) : null),
                    'taxId' => $queryCriteria->taxId,
                ],
                static function ($value): bool {
                    return $value !== null;
                }
            )
        );
    }

    protected function prepareRequestBody(): ?array
    {
        return $this->getCartAsArray($this->cart);
    }
}

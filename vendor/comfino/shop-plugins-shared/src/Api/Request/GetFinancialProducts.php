<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Dto\Payment\LoanQueryCriteria;
use Comfino\Api\Request;

class GetFinancialProducts extends Request
{
    /**
     * @param LoanQueryCriteria $queryCriteria
     */
    public function __construct(LoanQueryCriteria $queryCriteria)
    {
        $this->setRequestMethod('GET');
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
        return null;
    }
}

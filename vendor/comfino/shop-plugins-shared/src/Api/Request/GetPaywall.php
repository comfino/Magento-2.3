<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Dto\Payment\LoanQueryCriteria;
use Comfino\Api\Request;

class GetPaywall extends Request
{
    /**
     * @param LoanQueryCriteria $queryCriteria
     * @param string|null $recalculationUrl
     */
    public function __construct(LoanQueryCriteria $queryCriteria, ?string $recalculationUrl)
    {
        $this->setRequestMethod('GET');
        $this->setApiEndpointPath('shop-plugin-paywall');
        $this->setRequestParams(
            array_filter(
                [
                    'loanAmount' => $queryCriteria->loanAmount,
                    'loanTerm' => $queryCriteria->loanTerm,
                    'loanTypeSelected' => $queryCriteria->loanType,
                    'priceModifier' => $queryCriteria->priceModifier,
                    'productTypes' => ($queryCriteria->productTypes !== null ? implode(',', $queryCriteria->productTypes) : null),
                    'taxId' => $queryCriteria->taxId,
                ],
                static function ($value): bool {
                    return $value !== null;
                }
            )
        );

        if ($recalculationUrl !== null) {
            $this->setRequestHeaders(['Comfino-Recalculation-Url' => $recalculationUrl]);
        }
    }

    protected function prepareRequestBody(): ?array
    {
        return null;
    }
}

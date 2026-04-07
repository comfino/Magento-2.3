<?php

declare(strict_types=1);

namespace Comfino\Api\Response;

use Comfino\Api\Dto\Payment\FinancialProduct;
use Comfino\Api\Dto\Payment\LoanParameters;
use Comfino\Api\Dto\Payment\LoanTypeEnum;

class GetFinancialProducts extends Base
{
    public $financialProducts;

    /**
     * @param mixed[]|string|bool|null|float|int $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
        $this->checkResponseType($deserializedResponseBody, 'array');

        $financialProducts = [];

        foreach ($deserializedResponseBody as $financialProduct) {
            $this->checkResponseType($financialProduct, 'array');
            $this->checkResponseStructure(
                $financialProduct,
                ['name', 'type', 'icon', 'instalmentAmount', 'toPay', 'loanTerm', 'loanParameters']
            );
            $this->checkResponseType($financialProduct['loanParameters'], 'array', 'loanParameters');

            $financialProducts[] = new FinancialProduct(
                $financialProduct['name'],
                LoanTypeEnum::from($financialProduct['type']),
                $financialProduct['creditorName'],
                $financialProduct['description'] ?? '',
                $financialProduct['icon'],
                $financialProduct['instalmentAmount'],
                $financialProduct['toPay'],
                $financialProduct['loanTerm'],
                $financialProduct['rrso'] ?? 0.0,
                $financialProduct['representativeExample'] ?? '',
                $financialProduct['remarks'] ?? '',
                array_map(
                    function ($loanParams): LoanParameters {
                        $this->checkResponseType($loanParams, 'array', 'loanParams[]');
                        $this->checkResponseStructure($loanParams, ['instalmentAmount', 'toPay', 'loanTerm', 'rrso']);
                        $this->checkResponseType($loanParams['instalmentAmount'], 'integer', 'loanParams[][instalmentAmount]');
                        $this->checkResponseType($loanParams['toPay'], 'integer', 'loanParams[][toPay]');
                        $this->checkResponseType($loanParams['loanTerm'], 'integer', 'loanParams[][loanTerm]');
                        $this->checkResponseType($loanParams['rrso'], 'double', 'loanParams[][rrso]');

                        return new LoanParameters(
                            $loanParams['instalmentAmount'],
                            $loanParams['toPay'],
                            $loanParams['loanTerm'],
                            $loanParams['rrso'],
                            $loanParams['initialPaymentValue'] ?? null,
                            $loanParams['initialPaymentRate'] ?? null,
                            $loanParams['redemptionPaymentValue'] ?? null,
                            $loanParams['redemptionPaymentRate'] ?? null,
                            $loanParams['interest'] ?? null
                        );
                    },
                    $financialProduct['loanParameters']
                ),
                $financialProduct['initialPaymentValue'] ?? null,
                $financialProduct['initialPaymentRate'] ?? null,
                $financialProduct['redemptionPaymentValue'] ?? null,
                $financialProduct['redemptionPaymentRate'] ?? null,
                $financialProduct['offerRate'] ?? null
            );
        }

        $this->financialProducts = $financialProducts;
    }
}

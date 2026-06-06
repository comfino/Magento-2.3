<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Dto\Payment\AllowedProductConfig;
use Comfino\Api\Dto\Payment\LoanQueryCriteria;
use Comfino\Api\Request;

class GetFinancialProducts extends Request
{
    /**
     * @var mixed[]|null
     */
    private $allowedProductsConfig;

    /**
     * @param LoanQueryCriteria $queryCriteria
     */
    public function __construct(LoanQueryCriteria $queryCriteria)
    {
        $this->setRequestMethod('GET');
        $this->setApiEndpointPath('financial-products');
        $this->allowedProductsConfig = $queryCriteria->allowedProductsConfig;
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

    /**
     * @param string $apiHost
     * @param int $apiVersion
     */
    protected function getApiEndpointUri($apiHost, $apiVersion): string
    {
        $uri = parent::getApiEndpointUri($apiHost, $apiVersion);

        if (!empty($this->allowedProductsConfig)) {
            $configs = array_map(
                static function (AllowedProductConfig $prodConfig): array {
                    $entry = ['type' => (string) $prodConfig->type];

                    if ($prodConfig->maxTerm !== null) {
                        $entry['maxTerm'] = $prodConfig->maxTerm;
                    }
                    if ($prodConfig->minTerm !== null) {
                        $entry['minTerm'] = $prodConfig->minTerm;
                    }
                    if ($prodConfig->terms !== null) {
                        $entry['terms'] = $prodConfig->terms;
                    }

                    return $entry;
                },
                $this->allowedProductsConfig
            );

            $separator = strpos($uri, '?') !== false ? '&' : '?';
            $uri .= $separator . http_build_query(['allowedProductsConfig' => $configs]);
        }

        return $uri;
    }
}

<?php

namespace Comperia\ComperiaGateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_SANDBOX_ENABLED = 'payment/comperiapayment/sandbox';
    const XML_PATH_API_KEY = 'payment/comperiapayment/apikey';
    const XML_PATH_LOAN_TERM = 'payment/comperiapayment/loanterm';
    const XML_PATH_URL_PROD = 'payment/comperiapayment/produrl';
    const XML_PATH_URL_DEV = 'payment/comperiapayment/sandboxurl';

    /**
     * Return store configuration value by path
     *
     * @param string $path
     * @return mixed
     */
    protected function getConfigValue(string $path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Is sandbox activated
     * @return bool
     */
    public function isSandboxEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SANDBOX_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get API key
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_API_KEY);
    }

    /**
     * Get Loan Term
     * @return string|null
     */
    public function getLoanTerm(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_LOAN_TERM);
    }

    /**
     * Get Production host
     * @return string|null
     */
    public function getProdUrl(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_URL_PROD);
    }

    /**
     * Get Sandbox host
     * @return string|null
     */
    public function getSandboxUrl(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_URL_DEV);
    }
}

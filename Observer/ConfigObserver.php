<?php

namespace Comfino\ComfinoGateway\Observer;

use Comfino\Api\ApiClient;
use Comfino\Api\Exception\AccessDenied;
use Comfino\Api\Exception\AuthorizationError;
use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ErrorLogger;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigObserver implements ObserverInterface
{
    private WriterInterface $configWriter;
    private TypeListInterface $cacheTypeList;
    private ScopeConfigInterface $scopeConfig;
    private ManagerInterface $messageManager;

    public function __construct(
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $messageManager
    ) {
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
    }

    public function execute(Observer $observer): void
    {
        // Read freshly saved values directly via ScopeConfigInterface.
        // In a config-save POST request no Comfino config is read before this observer fires,
        // so ScopeConfigInterface has no stale in-memory cache for our paths.
        $apiKey = trim((string) $this->scopeConfig->getValue(Data::XML_PATH_API_KEY, ScopeInterface::SCOPE_STORE));
        $sandboxApiKey = trim((string) $this->scopeConfig->getValue(Data::XML_PATH_SANDBOX_API_KEY, ScopeInterface::SCOPE_STORE));
        $isSandbox = (bool) $this->scopeConfig->getValue(Data::XML_PATH_SANDBOX_ENABLED, ScopeInterface::SCOPE_STORE);
        $activeApiKey = $isSandbox ? $sandboxApiKey : $apiKey;
        $paywallTitle = trim((string) $this->scopeConfig->getValue(Data::XML_PATH_PAYWALL_TITLE, ScopeInterface::SCOPE_STORE));
        $minimalCartAmount = trim((string) $this->scopeConfig->getValue(Data::XML_PATH_MINIMAL_CART_AMOUNT, ScopeInterface::SCOPE_STORE));
        $priceObserverLevel = trim((string) $this->scopeConfig->getValue(Data::XML_PATH_WIDGET_PRICE_OBSERVER_LEVEL, ScopeInterface::SCOPE_STORE));
        $bannerCssUrl = trim((string) $this->scopeConfig->getValue(Data::XML_PATH_WIDGET_CUSTOM_BANNER_CSS_URL, ScopeInterface::SCOPE_STORE));
        $calculatorCssUrl = trim((string) $this->scopeConfig->getValue(Data::XML_PATH_WIDGET_CUSTOM_CALCULATOR_CSS_URL, ScopeInterface::SCOPE_STORE));

        $errors = [];

        // Required fields
        if (empty($apiKey)) {
            $errors[] = __('Field "%1" can not be empty.', __('Production API key'));
        }
        if (empty($paywallTitle)) {
            $errors[] = __('Field "%1" can not be empty.', __('Payment text'));
        }
        if (empty($minimalCartAmount)) {
            $errors[] = __('Field "%1" can not be empty.', __('Minimal amount in cart'));
        } elseif (!is_numeric($minimalCartAmount)) {
            $errors[] = __('Field "%1" has wrong numeric format.', __('Minimal amount in cart'));
        }

        // Numeric fields
        if ($priceObserverLevel !== '' && !is_numeric($priceObserverLevel)) {
            $errors[] = __('Field "%1" has wrong numeric format.', __('Price change detection - container hierarchy level'));
        }

        // Custom CSS URL validation
        foreach ([
            (string) __('Custom banner CSS style URL') => $bannerCssUrl,
            (string) __('Custom calculator CSS style URL') => $calculatorCssUrl,
        ] as $label => $url) {
            if (!empty($url)) {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors[] = __('Custom CSS URL "%1" is not valid.', $url);
                } elseif (parse_url($url, PHP_URL_HOST) === null) {
                    $errors[] = __('Custom CSS URL "%1" is not absolute.', $url);
                }
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->messageManager->addWarningMessage($error);
            }
            $this->messageManager->addErrorMessage(__('Settings not updated.'));
            return;
        }

        if (empty($activeApiKey)) {
            return;
        }

        ErrorLogger::init();

        // Validate API key and fetch widget key using the just-saved credentials.
        try {
            $apiClient = ApiClient::getInstance($isSandbox, $activeApiKey);

            // Verify key is accepted — throws AuthorizationError on 401, AccessDenied on 403.
            $apiClient->isShopAccountActive();

            // Persist widget key returned by API.
            try {
                $widgetKey = $apiClient->getWidgetKey();
                $this->configWriter->save(Data::XML_PATH_WIDGET_KEY, $widgetKey);
            } catch (\Throwable $e) {
                ApiClient::processApiError('ConfigObserver: get widget key error', $e);
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        } catch (AuthorizationError | AccessDenied $e) {
            $this->messageManager->addWarningMessage(__('API key %1 is not valid.', $activeApiKey));
            ApiClient::processApiError('ConfigObserver: API key validation error', $e);
        } catch (\Throwable $e) {
            ApiClient::processApiError('ConfigObserver: config save error', $e);
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $this->cacheTypeList->cleanType('config');
    }
}
<?php

namespace Comfino\Configuration;

use Comfino\Api\ApiClient;
use Comfino\Api\Dto\Payment\LoanTypeEnum;
use Comfino\Common\Backend\Payment\ProductTypeFilter\FilterByCartValueLowerLimit;
use Comfino\Common\Backend\Payment\ProductTypeFilter\FilterByExcludedCategory;
use Comfino\Common\Backend\Payment\ProductTypeFilterInterface;
use Comfino\Common\Backend\Payment\ProductTypeFilterManager;
use Comfino\Common\Shop\Cart;
use Comfino\Common\Shop\Product\CategoryFilter;
use Comfino\DebugLogger;
use Comfino\FinancialProduct\ProductTypesListTypeEnum;
use Comfino\PluginShared\CacheManager;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Thin wrapper over ProductTypeFilterManager for paywall product-type filtering.
 */
class SettingsManager
{
    private static ?ProductTypeFilterManager $filterManager;

    public static function getProductTypesSelectList(string $listType): array
    {
        $productTypes = self::getProductTypes($listType, true);

        if (isset($productTypes['error'])) {
            $productTypesList = [['value' => 'error', 'label' => $productTypes['error']]];
        } else {
            $productTypesList = [];

            foreach ($productTypes as $productTypeCode => $productTypeName) {
                $productTypesList[] = ['value' => $productTypeCode, 'label' => $productTypeName];
            }
        }

        return $productTypesList;
    }

    public static function getWidgetTypesSelectList(): array
    {
        $widgetTypes = self::getWidgetTypes(true);

        if (isset($widgetTypes['error'])) {
            $widgetTypesList = [['value' => 'error', 'label' => $widgetTypes['error']]];
        } else {
            $widgetTypesList = [];

            foreach ($widgetTypes as $widgetTypeCode => $widgetTypeName) {
                $widgetTypesList[] = ['value' => $widgetTypeCode, 'label' => $widgetTypeName];
            }
        }

        return $widgetTypesList;
    }

    /**
     * @return string[]
     */
    public static function getProductTypes(string $listType, bool $returnErrors = false): array
    {
        $language = substr(ObjectManager::getInstance()->get(ResolverInterface::class)->getLocale(), 0, 2);
        $cacheKey = "product_types.$listType.$language";
        $listTypeEnum = new ProductTypesListTypeEnum($listType);

        if (($productTypes = CacheManager::get($cacheKey)) !== null) {
            return is_array($productTypes) ? $productTypes : [];
        }

        if (empty(ApiClient::getInstance()->getApiKey())) {
            return $returnErrors ? ['error' => 'API key is required.'] : [];
        }

        try {
            $productTypes = ApiClient::getInstance()->getProductTypes($listTypeEnum);
            $productTypesList = $productTypes->productTypesWithNames;
            $cacheTtl = (int) $productTypes->getHeader('Cache-TTL', '0');

            CacheManager::set($cacheKey, $productTypesList, $cacheTtl, ['admin_product_types']);

            return $productTypesList;
        } catch (\Throwable $e) {
            ApiClient::processApiError('Product types error (Comfino API).', $e);

            if ($returnErrors) {
                return ['error' => $e->getMessage()];
            }
        }

        return [];
    }

    /**
     * @return string[]
     */
    public static function getProductTypesStrings(string $listType): array
    {
        $productTypes = self::getProductTypes($listType);

        if (isset($productTypes['error'])) {
            return [];
        }

        return array_keys($productTypes);
    }

    /**
     * @return LoanTypeEnum[]
     */
    public static function getProductTypesEnums(string $listType): array
    {
        $productTypes = self::getProductTypes($listType);

        if (isset($productTypes['error'])) {
            return [];
        }

        return array_map(
            static function (string $productType): LoanTypeEnum { return new LoanTypeEnum($productType, false); },
            array_keys($productTypes)
        );
    }

    /**
     * @return string[]
     */
    public static function getWidgetTypes(bool $returnErrors = false): array
    {
        $language = substr(ObjectManager::getInstance()->get(ResolverInterface::class)->getLocale(), 0, 2);
        $cacheKey = "widget_types.$language";

        if (($widgetTypes = CacheManager::get($cacheKey)) !== null) {
            return is_array($widgetTypes) ? $widgetTypes : [];
        }

        if (empty(ApiClient::getInstance()->getApiKey())) {
            return $returnErrors ? ['error' => 'API key is required.'] : [];
        }

        try {
            $widgetTypes = ApiClient::getInstance()->getWidgetTypes();
            $widgetTypesList = $widgetTypes->widgetTypesWithNames;
            $cacheTtl = (int) $widgetTypes->getHeader('Cache-TTL', '0');

            CacheManager::set($cacheKey, $widgetTypesList, $cacheTtl, ['admin_widget_types']);

            return $widgetTypesList;
        } catch (\Throwable $e) {
            ApiClient::processApiError('Widget types error (Comfino API).', $e);

            if ($returnErrors) {
                return ['error' => $e->getMessage()];
            }
        }

        return [];
    }

    /**
     * @return LoanTypeEnum[]|null
     */
    public static function getAllowedProductTypes(string $listType, Cart $cart, bool $returnOnlyArray = false): ?array
    {
        $filterManager = self::getFilterManager($listType);

        if (!$filterManager->filtersActive()) {
            return null;
        }

        $availableProductTypes = self::getProductTypesEnums($listType);
        $allowedProductTypes = $filterManager->getAllowedProductTypes($availableProductTypes, $cart);

        if (ConfigManager::isDebugMode()) {
            $activeFilters = array_map(
                static function (ProductTypeFilterInterface $filter): string {
                    return get_class($filter) . ': ' . json_encode($filter->getAsArray());
                },
                $filterManager->getFilters()
            );

            DebugLogger::logEvent(
                '[PAYWALL]',
                'getAllowedProductTypes',
                [
                    '$activeFilters' => $activeFilters,
                    '$availableProductTypes' => $availableProductTypes,
                    '$allowedProductTypes' => $allowedProductTypes,
                ]
            );
        }

        if ($returnOnlyArray) {
            return $allowedProductTypes;
        }

        return count($availableProductTypes) !== count($allowedProductTypes) ? $allowedProductTypes : null;
    }

    public static function getProductCategoryFilters(): array
    {
        if (!is_array($catFilters = ConfigManager::getConfigurationValue('COMFINO_PRODUCT_CATEGORY_FILTERS', []))) {
            $catFilters = array_map('trim', explode(',', $catFilters));
        }

        return $catFilters;
    }

    public static function getProductCategoryFiltersAvailProductTypes(): array
    {
        if (!is_array($availProds = ConfigManager::getConfigurationValue('COMFINO_CAT_FILTER_AVAIL_PROD_TYPES', []))) {
            $availProds = array_map('trim', explode(',', $availProds));
        }

        return $availProds;
    }

    public static function productCategoryFiltersActive(array $productCategoryFilters): bool
    {
        if (empty($productCategoryFilters)) {
            return false;
        }

        foreach ($productCategoryFilters as $excludedCategoryIds) {
            if (!empty($excludedCategoryIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns product types available for category filters, intersected with the configured
     * COMFINO_CAT_FILTER_AVAIL_PROD_TYPES list. Falls back to all paywall types on empty intersection.
     *
     * @return string[] ['PRODUCT_TYPE_CODE' => 'Product type name', ...]
     */
    public static function getCatFilterAvailProdTypes(): array
    {
        $productTypes = self::getProductTypes(ProductTypesListTypeEnum::LIST_TYPE_PAYWALL);

        if (empty($productTypes)) {
            return [];
        }

        $categoryFilterAvailProductTypes = [];

        foreach (self::getProductCategoryFiltersAvailProductTypes() as $productType) {
            $categoryFilterAvailProductTypes[$productType] = null;
        }

        if (empty($availProductTypes = array_intersect_key($productTypes, $categoryFilterAvailProductTypes))) {
            $availProductTypes = $productTypes;
        }

        return $availProductTypes;
    }

    private static function getFilterManager(string $listType): ProductTypeFilterManager
    {
        if (self::$filterManager === null) {
            self::$filterManager = ProductTypeFilterManager::getInstance();

            foreach (self::buildFiltersList($listType) as $filter) {
                self::$filterManager->addFilter($filter);
            }
        }

        return self::$filterManager;
    }

    /**
     * @return ProductTypeFilterInterface[]
     */
    private static function buildFiltersList(string $listType): array
    {
        $filters = [];
        $minAmount = (int) (round(ConfigManager::getConfigurationValue('COMFINO_MINIMAL_CART_AMOUNT', 0), 2) * 100);

        if ($minAmount > 0) {
            $availableProductTypes = self::getProductTypesStrings($listType);
            $filters[] = new FilterByCartValueLowerLimit(
                array_combine($availableProductTypes, array_fill(0, count($availableProductTypes), $minAmount))
            );
        }

        if (self::productCategoryFiltersActive($productCategoryFilters = self::getProductCategoryFilters())) {
            $filters[] = new FilterByExcludedCategory(
                new CategoryFilter(ConfigManager::getCategoriesTree()),
                $productCategoryFilters
            );
        }

        return $filters;
    }
}

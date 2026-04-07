<?php

namespace Comfino\Order;

use Comfino\Common\Shop\Cart;
use Comfino\Shop\Order\Cart\CartItem;
use Comfino\Shop\Order\Cart\Product;
use Comfino\Shop\Order\Customer;
use Comfino\Shop\Order\Customer\Address;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Converts Magento Quote/Order entities to Comfino Cart/Customer DTOs.
 *
 * @see Cart
 * @see CartItem
 */
final class OrderManager
{
    /**
     * Converts a Magento Quote to a Comfino Cart structure.
     *
     * @param Quote $quote Magento quote entity
     * @param int $priceModifier Optional price modifier in grosz
     *
     * @return Cart Comfino cart structure
     *
     * @throws LocalizedException
     */
    public static function getShopCart(Quote $quote, int $priceModifier = 0): Cart
    {
        $totalValue = (int) round(round($quote->getGrandTotal(), 2) * 100);

        if ($totalValue < 0) {
            throw new \InvalidArgumentException('Total value must be greater than 0.');
        }

        if ($priceModifier > 0 && $priceModifier < $totalValue) {
            // Add price modifier (e.g. custom commission).
            $totalValue += $priceModifier;
        }

        $cartItems = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();

            $taxPercent = $item->getTaxPercent();
            $hasTax = $taxPercent > 0.0;

            $productName = $item->getName();
            $grossPrice = (int) round(round($item->getPriceInclTax(), 2) * 100);
            $netPrice = $hasTax ? (int) round(round((float) $item->getPrice(), 2) * 100) : null;
            $taxValue = $hasTax ? $grossPrice - $netPrice : null;
            $taxRate = $hasTax ? (int) $taxPercent : null;
            $quantity = (int) $item->getQty();

            $productId = (string) $product->getId();
            $categoryIds = self::getProductCategoryIds($product);
            $categoryNames = self::getProductCategoryNames($categoryIds);
            $categories = !empty($categoryNames) ? implode('→', $categoryNames) : null;
            $ean = $product->getSku();
            $imageUrl = self::getProductImageUrl($product);

            $cartItems[] = new CartItem(
                new Product(
                    $productName,
                    $grossPrice,
                    $productId,
                    $categories,
                    $ean,
                    $imageUrl,
                    $categoryIds,
                    $netPrice,
                    $taxRate,
                    $taxValue
                ),
                $quantity
            );
        }

        $totalNetValue = 0;
        $totalTaxValue = 0;

        foreach ($cartItems as $cartItem) {
            if ($cartItem->getProduct()->getNetPrice() !== null) {
                $totalNetValue += $cartItem->getProduct()->getNetPrice() * $cartItem->getQuantity();
            }

            if ($cartItem->getProduct()->getTaxValue() !== null) {
                $totalTaxValue += $cartItem->getProduct()->getTaxValue() * $cartItem->getQuantity();
            }
        }

        if (is_float($totalNetValue) || $totalNetValue > PHP_INT_MAX) {
            throw new \InvalidArgumentException('Total net value must be integer not greater than PHP_INT_MAX.');
        }

        if (is_float($totalTaxValue) || $totalTaxValue > PHP_INT_MAX) {
            throw new \InvalidArgumentException('Total tax value must be integer not greater than PHP_INT_MAX.');
        }

        if ($totalNetValue === 0) {
            $totalNetValue = null;
        }

        if ($totalTaxValue === 0) {
            $totalTaxValue = null;
        }

        $shippingAddress = $quote->getShippingAddress();
        $deliveryCost = 0;
        $deliveryNetCost = null;
        $deliveryTaxValue = null;
        $deliveryTaxRate = null;

        if ($shippingAddress !== null) {
            $deliveryCost = (int) round(round($shippingAddress->getShippingInclTax(), 2) * 100);

            if ($shippingAddress->getShippingTaxAmount() > 0.0) {
                $deliveryNetCost = (int) round(round($shippingAddress->getShippingAmount(), 2) * 100);
                $deliveryTaxValue = $deliveryCost - $deliveryNetCost;

                if ($deliveryNetCost > 0) {
                    $deliveryTaxRate = (int) round($deliveryTaxValue / $deliveryNetCost * 100);
                } elseif ($deliveryCost !== 0) {
                    $deliveryTaxRate = (int) round($deliveryTaxValue / $deliveryCost * 100);
                }
            }
        }

        return new Cart(
            $totalValue,
            $totalNetValue,
            $totalTaxValue,
            $deliveryCost,
            $deliveryNetCost,
            $deliveryTaxRate,
            $deliveryTaxValue,
            $cartItems
        );
    }

    /**
     * Extracts customer information from a Magento order.
     *
     * Collects customer data from shipping and billing addresses with fallback logic:
     * - Names taken from billing address (fallback to shipping when billing has no firstname).
     * - Phone taken from billing address, overridden by shipping phone if available.
     * - Delivery address fields taken from shipping address (fallback to billing for virtual orders).
     * - Street line 1 is parsed to separate street name and building number.
     * - Street line 2 (if present) is used as apartment number.
     *
     * @param MagentoOrder $order Magento order entity
     * @param string $remoteAddress Customer IP address
     * @param bool $isLoggedIn Whether the customer is logged in
     *
     * @return Customer|null null if no address data is available
     */
    public static function getShopCustomerFromOrder(
        MagentoOrder $order,
        string $remoteAddress,
        bool $isLoggedIn
    ): ?Customer {
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        // Virtual orders have no shipping address - fall back to billing for all address data.
        if ($shippingAddress === null) {
            $shippingAddress = $billingAddress;
        }

        if ($billingAddress === null) {
            $billingAddress = $shippingAddress;
        }

        if ($shippingAddress === null && $billingAddress === null) {
            return null;
        }

        // Phone: start from billing, override with shipping phone if available.
        $phoneNumber = trim($billingAddress->getTelephone() ?? '');
        $shippingPhone = trim($shippingAddress->getTelephone() ?? '');

        if (!empty($shippingPhone)) {
            $phoneNumber = $shippingPhone;
        }

        if (!empty(trim($billingAddress->getFirstname() ?? ''))) {
            // Use billing address to get customer names.
            [$firstName, $lastName] = self::prepareCustomerNames($billingAddress);
        } else {
            // Use delivery address to get customer names.
            [$firstName, $lastName] = self::prepareCustomerNames($shippingAddress);
        }

        // Delivery address: prefer shipping (has postal code), fall back to billing.
        $deliveryAddress = !empty($shippingAddress->getPostcode()) ? $shippingAddress : $billingAddress;

        $streetLines = $deliveryAddress->getStreet() ?? [];
        $streetLine1 = trim($streetLines[0] ?? '');
        $streetLine2 = trim($streetLines[1] ?? '');

        [$street, $buildingNumber] = self::parseStreetAndBuildingNumber($streetLine1);

        $apartmentNumber = !empty($streetLine2) ? $streetLine2 : null;
        $isRegular = $order->getCustomerId() !== null;

        $customerTaxId = trim(str_replace('-', '', $billingAddress->getVatId() ?? ''));
        $taxId = preg_match('/^[A-Z]{0,3}\d{7,}$/', $customerTaxId) ? $customerTaxId : null;

        return new Customer(
            $firstName,
            $lastName,
            (string) ($order->getCustomerEmail() ?? ''),
            $phoneNumber,
            $remoteAddress,
            $taxId,
            $isRegular,
            $isLoggedIn,
            new Address(
                $street,
                $buildingNumber,
                $apartmentNumber,
                $deliveryAddress->getPostcode(),
                $deliveryAddress->getCity(),
                $deliveryAddress->getCountryId() ?? 'PL'
            )
        );
    }

    /**
     * Returns active category IDs for a product.
     *
     * @return int[]
     */
    private static function getProductCategoryIds(\Magento\Catalog\Model\Product $product): array
    {
        return array_map('intval', $product->getCategoryIds() ?? []);
    }

    /**
     * Returns a map of category ID => category name for the given category IDs, filtered to active categories only.
     *
     * @param int[] $categoryIds
     *
     * @return string[] [categoryId => categoryName, ...]
     *
     * @throws LocalizedException
     */
    private static function getProductCategoryNames(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        /** @var CategoryCollectionFactory $collectionFactory */
        $collectionFactory = ObjectManager::getInstance()->get(CategoryCollectionFactory::class);

        $collection = $collectionFactory->create();
        $collection
            ->addFieldToFilter('entity_id', ['in' => $categoryIds])
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToSelect('name');

        $categoryNames = [];

        foreach ($collection as $cat) {
            $categoryNames[(int) $cat->getId()] = (string) $cat->getName();
        }

        return $categoryNames;
    }

    /**
     * Extracts and normalizes customer first and last names from an order address.
     *
     * When last name is missing, attempts to split the first name by space so that both fields are
     * populated (e.g. "Jan Kowalski" → ["Jan", "Kowalski"]).
     *
     * @param OrderAddressInterface $address
     *
     * @return array{0: string, 1: string} [firstName, lastName]
     */
    private static function prepareCustomerNames(OrderAddressInterface $address): array
    {
        $firstName = trim($address->getFirstname() ?? '');
        $lastName = trim($address->getLastname() ?? '');

        if (empty($lastName)) {
            $nameParts = explode(' ', $firstName);

            if (count($nameParts) > 1) {
                [$firstName, $lastName] = $nameParts;
            }
        }

        return [$firstName, $lastName];
    }

    /**
     * Parses a street address line into a street name and a building number.
     *
     * Scans the tokens from the end; the last token matching a numeric pattern (digits optionally followed by one
     * letter, e.g. "15", "15a") is treated as the building number and the remainder becomes the street name.
     *
     * @param string $streetLine Full street line (e.g. "Main St 15a")
     *
     * @return array{0: string, 1: string|null} [streetName, buildingNumber|null]
     */
    private static function parseStreetAndBuildingNumber(string $streetLine): array
    {
        $addressParts = explode(' ', $streetLine);
        $buildingNumber = null;

        if (count($addressParts) > 1) {
            foreach ($addressParts as $idx => $part) {
                if (preg_match('/^\d+[a-zA-Z]?$/', trim($part))) {
                    $buildingNumber = trim($part);
                    $streetLine = trim(implode(' ', array_slice($addressParts, 0, $idx)));

                    break;
                }
            }
        }

        return [$streetLine, $buildingNumber];
    }

    /**
     * Builds a Comfino Cart from a single Magento product, for use with product type filters and widget data.
     *
     * The returned Cart contains a single item and no delivery cost. Tax data (net price, tax rate, tax amount)
     * is resolved via the store's tax configuration, mirroring the per-item logic in getShopCart().
     *
     * @throws LocalizedException
     */
    public static function getShopCartFromProduct(\Magento\Catalog\Model\Product $product): Cart
    {
        /** @var CatalogHelper $catalogHelper */
        $catalogHelper = ObjectManager::getInstance()->get(CatalogHelper::class);
        $finalPrice = $product->getFinalPrice();

        $grossPrice = (int) round(round((float) $catalogHelper->getTaxPrice($product, $finalPrice, true), 2) * 100);
        $netPriceInt = (int) round(round((float) $catalogHelper->getTaxPrice($product, $finalPrice, false), 2) * 100);

        $hasTax = $netPriceInt > 0 && $netPriceInt !== $grossPrice;
        $netPrice = $hasTax ? $netPriceInt : null;
        $taxValue = $hasTax ? $grossPrice - $netPriceInt : null;
        $taxRate = $hasTax ? (int) round(($grossPrice - $netPriceInt) / $netPriceInt * 100) : null;

        $categoryIds = self::getProductCategoryIds($product);
        $categoryNames = self::getProductCategoryNames($categoryIds);
        $categories = !empty($categoryNames) ? implode('→', $categoryNames) : null;

        return new Cart(
            $grossPrice,
            $netPrice,
            $taxValue,
            0,
            null,
            null,
            null,
            [new CartItem(
                new Product(
                    $product->getName(),
                    $grossPrice,
                    (string) $product->getId(),
                    $categories,
                    $product->getSku(),
                    self::getProductImageUrl($product),
                    $categoryIds,
                    $netPrice,
                    $taxRate,
                    $taxValue
                ),
                1
            )]
        );
    }

    /**
     * Returns the full URL of the product's main image, or null if no image is set.
     */
    private static function getProductImageUrl(\Magento\Catalog\Model\Product $product): ?string
    {
        $image = $product->getImage();

        if (empty($image) || $image === 'no_selection') {
            return null;
        }

        $mediaUrl = ObjectManager::getInstance()
            ->get(StoreManagerInterface::class)
            ->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        return rtrim($mediaUrl, '/') . '/catalog/product' . $image;
    }
}

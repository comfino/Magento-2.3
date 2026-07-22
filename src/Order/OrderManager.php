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

            /* When taxPercent is null, the product is VAT-free (taxRate = null); when it is 0, the rate is explicit
               0% VAT (taxRate = 0). In both cases net price equals gross price and tax value is 0. */
            $taxPercent = $item->getTaxPercent();
            $hasTax = ($taxPercent !== null && (float) $taxPercent > 0.0);
            $isVatFree = ($taxPercent === null);

            $productName = $item->getName();
            $grossPrice = (int) round(round($item->getPriceInclTax(), 2) * 100);
            $netPrice = $hasTax ? (int) round(round((float) $item->getPrice(), 2) * 100) : $grossPrice;
            $taxValue = $hasTax ? $grossPrice - $netPrice : 0;
            $taxRate = $isVatFree ? null : ($hasTax ? (int) $taxPercent : 0);
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

        [$totalNetValue, $totalTaxValue] = self::sumCartTaxTotals($cartItems);

        $shippingAddress = $quote->getShippingAddress();
        $deliveryCost = 0;
        $deliveryNetCost = null;
        $deliveryTaxValue = null;
        $deliveryTaxRate = null;

        if ($shippingAddress !== null) {
            [$deliveryCost, $deliveryNetCost, $deliveryTaxRate, $deliveryTaxValue] = self::calculateDelivery(
                (float) $shippingAddress->getShippingInclTax(),
                (float) $shippingAddress->getShippingAmount(),
                (float) $shippingAddress->getShippingTaxAmount()
            );
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
     * Converts a Magento Order to a Comfino Cart structure.
     *
     * Order-side counterpart of getShopCart(): used after order placement (e.g., in ApplicationService), when the
     * source quote is no longer reliable. Produces the same rich cart items (category path, EAN, image, per-item
     * tax) and delivery cost breakdown as getShopCart(), derived from the persisted order instead of the quote.
     *
     * @param MagentoOrder $order Magento order entity
     *
     * @return Cart Comfino cart structure
     *
     * @throws LocalizedException
     */
    public static function getShopCartFromOrder(MagentoOrder $order): Cart
    {
        $totalValue = (int) round(round((float) $order->getGrandTotal(), 2) * 100);

        if ($totalValue < 0) {
            throw new \InvalidArgumentException('Total value must be greater than 0.');
        }

        $cartItems = [];

        foreach ($order->getAllVisibleItems() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            $product = $item->getProduct();

            /* When taxPercent is null, the product is VAT-free (taxRate = null); when it is 0, the rate is explicit
               0% VAT (taxRate = 0). In both cases net price equals gross price and tax value is 0. */
            $taxPercent = $item->getTaxPercent();
            $hasTax = ($taxPercent !== null && (float) $taxPercent > 0.0);
            $isVatFree = ($taxPercent === null);

            $grossPrice = (int) round(round((float) $item->getPriceInclTax(), 2) * 100);
            $netPrice = $hasTax ? (int) round(round((float) $item->getPrice(), 2) * 100) : $grossPrice;
            $taxValue = $hasTax ? $grossPrice - $netPrice : 0;
            $taxRate = $isVatFree ? null : ($hasTax ? (int) $taxPercent : 0);
            $quantity = (int) $item->getQtyOrdered();

            $productId = $product !== null ? (string) $product->getId() : null;
            $categoryIds = $product !== null ? self::getProductCategoryIds($product) : [];
            $categoryNames = self::getProductCategoryNames($categoryIds);
            $categories = !empty($categoryNames) ? implode('→', $categoryNames) : null;
            $ean = $product !== null ? $product->getSku() : null;
            $imageUrl = $product !== null ? self::getProductImageUrl($product) : null;

            $cartItems[] = new CartItem(
                new Product(
                    (string) $item->getName(),
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

        [$totalNetValue, $totalTaxValue] = self::sumCartTaxTotals($cartItems);
        [$deliveryCost, $deliveryNetCost, $deliveryTaxRate, $deliveryTaxValue] = self::calculateDelivery(
            (float) $order->getShippingInclTax(),
            (float) $order->getShippingAmount(),
            (float) $order->getShippingTaxAmount()
        );

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
     * - Delivery address fields taken from the shipping address (fallback to billing for virtual orders).
     * - Street line 1 is parsed to separate street name and building number.
     * - Street line 2 (if present) is used as an apartment number.
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
     * Sums the per-item net and tax values across all cart items.
     *
     * @param CartItem[] $cartItems
     *
     * @return array{0: int|null, 1: int|null} [totalNetValue, totalTaxValue]; null when the sum is zero.
     */
    private static function sumCartTaxTotals(array $cartItems): array
    {
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

        return [
            $totalNetValue === 0 ? null : $totalNetValue,
            $totalTaxValue === 0 ? null : $totalTaxValue,
        ];
    }

    /**
     * Computes the delivery cost breakdown (in grosz) from gross/net/tax shipping amounts.
     *
     * @return array{0: int, 1: int|null, 2: int|null, 3: int|null}
     *         [deliveryCost, deliveryNetCost, deliveryTaxRate, deliveryTaxValue]
     */
    private static function calculateDelivery(float $shippingGross, float $shippingNet, float $shippingTaxAmount): array
    {
        $deliveryCost = (int) round(round($shippingGross, 2) * 100);

        if ($deliveryCost === 0) {
            // Free delivery - no cost breakdown.
            return [0, null, null, null];
        }

        /* Paid delivery with no VAT (explicit 0% or VAT-free): net equals gross, tax value is 0 and the rate is
           null. When VAT applies, use the actual net shipping amount and derive the rate from net (or gross). */
        if ($shippingTaxAmount > 0.0) {
            $deliveryNetCost = (int) round(round($shippingNet, 2) * 100);
        } else {
            $deliveryNetCost = $deliveryCost;
        }

        $deliveryTaxValue = $deliveryCost - $deliveryNetCost;

        if ($deliveryNetCost >= $deliveryCost) {
            $deliveryTaxRate = null;
        } elseif ($deliveryNetCost > 0) {
            $deliveryTaxRate = (int) round($deliveryTaxValue / $deliveryNetCost * 100);
        } else {
            $deliveryTaxRate = (int) round($deliveryTaxValue / $deliveryCost * 100);
        }

        return [$deliveryCost, $deliveryNetCost, $deliveryTaxRate, $deliveryTaxValue];
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

        /* The catalog helper exposes gross vs. net price but cannot distinguish explicit 0% VAT from VAT-exempt;
           taxRate is therefore null whenever gross equals net, covering both no-VAT cases uniformly. Net price
           equals gross price and tax value is 0 when there is no tax. */
        $hasTax = $netPriceInt > 0 && $netPriceInt !== $grossPrice;
        $taxValue = $hasTax ? $grossPrice - $netPriceInt : 0;
        $taxRate = $hasTax ? (int) round(($grossPrice - $netPriceInt) / $netPriceInt * 100) : null;
        $netPrice = $hasTax ? $netPriceInt : $grossPrice;

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

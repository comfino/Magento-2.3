<?php

declare(strict_types=1);

namespace Comfino\Shop\Order\Cart;

class Product implements ProductInterface
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    private $price;
    /**
     * @var string|null
     */
    private $id;
    /**
     * @var string|null
     */
    private $category;
    /**
     * @var string|null
     */
    private $ean;
    /**
     * @var string|null
     */
    private $photoUrl;
    /**
     * @var int[]|null
     */
    private $categoryIds;
    /**
     * @var int|null
     */
    private $netPrice;
    /**
     * @var int|null
     */
    private $taxRate;
    /**
     * @var int|null
     */
    private $taxValue;
    /**
     * @param string $name
     * @param int $price
     * @param string|null $id
     * @param string|null $category
     * @param string|null $ean
     * @param string|null $photoUrl
     * @param int[]|null $categoryIds
     * @param int|null $netPrice
     * @param int|null $taxRate
     * @param int|null $taxValue
     */
    public function __construct(string $name, int $price, ?string $id = null, ?string $category = null, ?string $ean = null, ?string $photoUrl = null, ?array $categoryIds = null, ?int $netPrice = null, ?int $taxRate = null, ?int $taxValue = null)
    {
        $this->name = $name;
        $this->price = $price;
        $this->id = $id;
        $this->category = $category;
        $this->ean = $ean;
        $this->photoUrl = $photoUrl;
        $this->categoryIds = $categoryIds;
        $this->netPrice = $netPrice;
        $this->taxRate = $taxRate;
        $this->taxValue = $taxValue;
    }

    public function getName(): string
    {
        return trim(html_entity_decode(strip_tags($this->name)));
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getNetPrice(): ?int
    {
        return $this->netPrice;
    }

    public function getTaxRate(): ?int
    {
        return $this->taxRate;
    }

    public function getTaxValue(): ?int
    {
        return $this->taxValue;
    }

    public function getId(): ?string
    {
        return $this->id !== null ? trim(strip_tags($this->id)) : null;
    }

    public function getCategory(): ?string
    {
        return $this->category !== null ? trim(strip_tags($this->category)) : null;
    }

    public function getEan(): ?string
    {
        return $this->ean !== null ? trim(strip_tags($this->ean)) : null;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl !== null ? trim(strip_tags($this->photoUrl)) : null;
    }

    public function getCategoryIds(): ?array
    {
        return $this->categoryIds;
    }
}

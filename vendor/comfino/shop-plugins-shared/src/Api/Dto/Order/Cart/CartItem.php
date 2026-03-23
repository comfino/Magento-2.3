<?php

declare(strict_types=1);

namespace Comfino\Api\Dto\Order\Cart;

class CartItem
{
    public $name;

    public $price;

    public $netPrice;

    public $vatRate;

    public $vatAmount;

    public $quantity;

    public $externalId;

    public $photoUrl;

    public $ean;

    public $category;

    /**
     * @param string $name
     * @param int $price
     * @param int $quantity
     * @param string|null $externalId
     * @param string|null $photoUrl
     * @param string|null $ean
     * @param string|null $category
     * @param int|null $netPrice
     * @param int|null $vatRate
     * @param int|null $vatAmount
     */
    public function __construct(
        string $name,
        int $price,
        int $quantity,
        ?string $externalId = null,
        ?string $photoUrl = null,
        ?string $ean = null,
        ?string $category = null,
        ?int $netPrice = null,
        ?int $vatRate = null,
        ?int $vatAmount = null
    ) {
        $this->name = $name;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->externalId = $externalId;
        $this->photoUrl = $photoUrl;
        $this->ean = $ean;
        $this->category = $category;
        $this->netPrice = $netPrice;
        $this->vatRate = $vatRate;
        $this->vatAmount = $vatAmount;
    }
}

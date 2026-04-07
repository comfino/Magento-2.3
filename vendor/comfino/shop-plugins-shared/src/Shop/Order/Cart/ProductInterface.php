<?php

declare(strict_types=1);

namespace Comfino\Shop\Order\Cart;

interface ProductInterface
{
    public function getName(): string;

    public function getPrice(): int;

    public function getNetPrice(): ?int;

    public function getTaxRate(): ?int;

    public function getTaxValue(): ?int;

    public function getId(): ?string;

    public function getCategory(): ?string;

    public function getEan(): ?string;

    public function getPhotoUrl(): ?string;

    public function getCategoryIds(): ?array;
}

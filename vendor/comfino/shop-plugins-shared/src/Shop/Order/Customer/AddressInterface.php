<?php

declare(strict_types=1);

namespace Comfino\Shop\Order\Customer;

interface AddressInterface
{
    public function getStreet(): ?string;

    public function getBuildingNumber(): ?string;

    public function getApartmentNumber(): ?string;

    public function getPostalCode(): ?string;

    public function getCity(): ?string;

    public function getCountryCode(): ?string;
}

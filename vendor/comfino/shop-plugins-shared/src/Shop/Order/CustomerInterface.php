<?php

declare(strict_types=1);

namespace Comfino\Shop\Order;

use Comfino\Shop\Order\Customer\AddressInterface;

interface CustomerInterface
{
    public function getFirstName(): string;

    public function getLastName(): string;

    public function getEmail(): string;

    public function getPhoneNumber(): string;

    public function getIp(): string;

    public function getTaxId(): ?string;

    public function isRegular(): ?bool;

    public function isLogged(): ?bool;

    public function getAddress(): ?AddressInterface;
}

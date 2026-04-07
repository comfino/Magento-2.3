<?php

declare(strict_types=1);

namespace Comfino\Shop\Order;

use Comfino\Shop\Order\Customer\AddressInterface;

class Customer implements CustomerInterface
{
    /**
     * @var string
     */
    private $firstName;
    /**
     * @var string
     */
    private $lastName;
    /**
     * @var string
     */
    private $email;
    /**
     * @var string
     */
    private $phoneNumber;
    /**
     * @var string
     */
    private $ip;
    /**
     * @var string|null
     */
    private $taxId;
    /**
     * @var bool|null
     */
    private $isRegular;
    /**
     * @var bool|null
     */
    private $isLogged;
    /**
     * @var AddressInterface|null
     */
    private $address;
    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $phoneNumber
     * @param string $ip
     * @param string|null $taxId
     * @param bool|null $isRegular
     * @param bool|null $isLogged
     * @param AddressInterface|null $address
     */
    public function __construct(string $firstName, string $lastName, string $email, string $phoneNumber, string $ip, ?string $taxId = null, ?bool $isRegular = null, ?bool $isLogged = null, ?AddressInterface $address = null)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
        $this->ip = $ip;
        $this->taxId = $taxId;
        $this->isRegular = $isRegular;
        $this->isLogged = $isLogged;
        $this->address = $address;
    }

    public function getFirstName(): string
    {
        return trim(strip_tags($this->firstName));
    }

    public function getLastName(): string
    {
        return trim(strip_tags($this->lastName));
    }

    public function getEmail(): string
    {
        return trim(strip_tags($this->email));
    }

    public function getPhoneNumber(): string
    {
        return trim(strip_tags($this->phoneNumber));
    }

    public function getIp(): string
    {
        return trim($this->ip);
    }

    public function getTaxId(): ?string
    {
        return $this->taxId !== null ? trim(strip_tags($this->taxId)) : null;
    }

    public function isRegular(): ?bool
    {
        return $this->isRegular;
    }

    public function isLogged(): ?bool
    {
        return $this->isLogged;
    }

    public function getAddress(): ?AddressInterface
    {
        return $this->address;
    }
}

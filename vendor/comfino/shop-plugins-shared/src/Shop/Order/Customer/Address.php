<?php

declare(strict_types=1);

namespace Comfino\Shop\Order\Customer;

class Address implements AddressInterface
{
    /**
     * @var string|null
     */
    private $street;
    /**
     * @var string|null
     */
    private $buildingNumber;
    /**
     * @var string|null
     */
    private $apartmentNumber;
    /**
     * @var string|null
     */
    private $postalCode;
    /**
     * @var string|null
     */
    private $city;
    /**
     * @var string|null
     */
    private $countryCode;
    /**
     * @param string|null $street
     * @param string|null $buildingNumber
     * @param string|null $apartmentNumber
     * @param string|null $postalCode
     * @param string|null $city
     * @param string|null $countryCode
     */
    public function __construct(?string $street = null, ?string $buildingNumber = null, ?string $apartmentNumber = null, ?string $postalCode = null, ?string $city = null, ?string $countryCode = null)
    {
        $this->street = $street;
        $this->buildingNumber = $buildingNumber;
        $this->apartmentNumber = $apartmentNumber;
        $this->postalCode = $postalCode;
        $this->city = $city;
        $this->countryCode = $countryCode;
    }

    public function getStreet(): ?string
    {
        return $this->street !== null ? trim(html_entity_decode(strip_tags($this->street))) : null;
    }

    public function getBuildingNumber(): ?string
    {
        return $this->buildingNumber ? trim(html_entity_decode(strip_tags($this->buildingNumber))) : null;
    }

    public function getApartmentNumber(): ?string
    {
        return $this->apartmentNumber ? trim(html_entity_decode(strip_tags($this->apartmentNumber))) : null;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode ? trim(html_entity_decode(strip_tags($this->postalCode))) : null;
    }

    public function getCity(): ?string
    {
        return $this->city ? trim(html_entity_decode(strip_tags($this->city))) : null;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode ? trim(html_entity_decode(strip_tags($this->countryCode))) : null;
    }
}

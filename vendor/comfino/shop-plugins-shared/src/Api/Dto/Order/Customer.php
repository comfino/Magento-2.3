<?php

declare(strict_types=1);

namespace Comfino\Api\Dto\Order;

use Comfino\Api\Dto\Order\Customer\Address;

class Customer
{
    public $firstName;

    public $lastName;

    public $email;

    public $phoneNumber;

    public $ip;

    public $taxId;

    public $regular;

    public $logged;

    public $address;

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $phoneNumber
     * @param string $ip
     * @param string|null $taxId
     * @param bool|null $regular
     * @param bool|null $logged
     * @param Address|null $address
     */
    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        string $phoneNumber,
        string $ip,
        ?string $taxId,
        ?bool $regular,
        ?bool $logged,
        ?Address $address
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
        $this->ip = $ip;
        $this->taxId = $taxId;
        $this->regular = $regular;
        $this->logged = $logged;
        $this->address = $address;
    }
}

<?php

declare(strict_types=1);

namespace Comfino\Shop\Order;

interface OrderInterface
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string|null
     */
    public function getNotifyUrl(): ?string;

    /**
     * @return string
     */
    public function getReturnUrl(): string;

    public function getLoanParameters(): LoanParametersInterface;

    public function getCart(): CartInterface;

    public function getCustomer(): CustomerInterface;

    public function getSeller(): ?SellerInterface;

    public function getAccountNumber(): ?string;

    public function getTransferTitle(): ?string;
}

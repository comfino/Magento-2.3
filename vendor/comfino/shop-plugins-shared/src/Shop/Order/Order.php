<?php

declare(strict_types=1);

namespace Comfino\Shop\Order;

class Order implements OrderInterface
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $returnUrl;
    /**
     * @var LoanParametersInterface
     */
    private $loanParameters;
    /**
     * @var CartInterface
     */
    private $cart;
    /**
     * @var CustomerInterface
     */
    private $customer;
    /**
     * @var string|null
     */
    private $notifyUrl;
    /**
     * @var SellerInterface|null
     */
    private $seller;
    /**
     * @var string|null
     */
    private $accountNumber;
    /**
     * @var string|null
     */
    private $transferTitle;
    /**
     * @param string $id
     * @param string $returnUrl
     * @param LoanParametersInterface $loanParameters
     * @param CartInterface $cart
     * @param CustomerInterface $customer
     * @param string|null $notifyUrl
     * @param SellerInterface|null $seller
     * @param string|null $accountNumber
     * @param string|null $transferTitle
     */
    public function __construct(string $id, string $returnUrl, LoanParametersInterface $loanParameters, CartInterface $cart, CustomerInterface $customer, ?string $notifyUrl = null, ?SellerInterface $seller = null, ?string $accountNumber = null, ?string $transferTitle = null)
    {
        $this->id = $id;
        $this->returnUrl = $returnUrl;
        $this->loanParameters = $loanParameters;
        $this->cart = $cart;
        $this->customer = $customer;
        $this->notifyUrl = $notifyUrl;
        $this->seller = $seller;
        $this->accountNumber = $accountNumber;
        $this->transferTitle = $transferTitle;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNotifyUrl(): ?string
    {
        return $this->notifyUrl !== null ? trim(strip_tags($this->notifyUrl)) : null;
    }

    public function getReturnUrl(): string
    {
        return trim(strip_tags($this->returnUrl));
    }

    public function getLoanParameters(): LoanParametersInterface
    {
        return $this->loanParameters;
    }

    public function getCart(): CartInterface
    {
        return $this->cart;
    }

    public function getCustomer(): CustomerInterface
    {
        return $this->customer;
    }

    public function getSeller(): ?SellerInterface
    {
        return $this->seller;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber !== null ? trim(html_entity_decode(strip_tags($this->accountNumber))) : null;
    }

    public function getTransferTitle(): ?string
    {
        return $this->transferTitle !== null ? trim(html_entity_decode(strip_tags($this->transferTitle))) : null;
    }
}

<?php

namespace Comperia\ComperiaGateway\Connector\Transaction;

/**
 * Class Transaction
 *
 * @package Comperia\ComperiaGateway\Connector\Transaction
 */
abstract class Transaction implements TransactionInterface
{
    /**
     * @var string
     */
    private $body;

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return Transaction
     */
    public function setBody(string $body): Transaction
    {
        $this->body = $body;

        return $this;
    }
}

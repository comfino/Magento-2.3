<?php

namespace Comperia\ComperiaGateway\Connector\Transaction;

/**
 * Interface TransactionInterface
 *
 * @package Comperia\ComperiaGateway\Connector\Transaction
 */
interface TransactionInterface
{
    /**
     * @return string
     */
    public function getBody(): string;

    /**
     * @param string $body
     *
     * @return Transaction
     */
    public function setBody(string $body): Transaction;
}

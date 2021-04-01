<?php

namespace Comperia\ComperiaGateway\Connector\Transaction\Response;

use Symfony\Component\HttpFoundation\Response;

abstract class TransactionResponse
{
    /**
     * @var int
     */
    private $code;
    /**
     * @var array
     */
    private $body;

    public function __construct(int $code, array $body)
    {
        $this->code = $code;
        $this->body = $body;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int $code
     *
     * @return TransactionResponse
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return array
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * @param array $body
     *
     * @return TransactionResponse
     */
    public function setBody(array $body): TransactionResponse
    {
        $this->body = $body;

        return $this;
    }

    /*
     * @return bool
     */
    public function isSuccessfull()
    {
        $positiveHttpCodes = [
            Response::HTTP_ACCEPTED,
            Response::HTTP_OK,
            Response::HTTP_CONTINUE,
            Response::HTTP_CREATED
        ];

        return in_array($this->code, $positiveHttpCodes) ? true : false;
    }
}

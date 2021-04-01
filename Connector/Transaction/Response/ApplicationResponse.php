<?php

namespace Comperia\ComperiaGateway\Connector\Transaction\Response;

/**
 * Class ApplicationResponse
 *
 * @package Comperia\ComperiaGateway\Connector\Transaction\Response
 */
final class ApplicationResponse extends TransactionResponse
{
    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $externalId;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var string
     */
    private $href;

    /**
     * ApplicationResponse constructor.
     *
     * @param int   $code
     * @param array $body
     */
    public function __construct(int $code, array $body)
    {
        parent::__construct($code, $body);
        if (parent::isSuccessfull()) {
            $this->status = $body['status'];
            $this->externalId = $body['externalId'];
            $this->redirectUri = $body['applicationUrl'];
            $this->href = $body['_links']['self']['href'];
        }
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->href;
    }

}

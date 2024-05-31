<?php

namespace Comfino\ComfinoGateway\Model\Connector\Transaction\Response;

use Comfino\ComfinoGateway\Api\Data\ApplicationResponseInterface;
use Magento\Framework\DataObject;
use Symfony\Component\HttpFoundation\Response;

class ApplicationResponse extends DataObject implements ApplicationResponseInterface
{
    private const POSITIVE_HTTP_CODES = [
        Response::HTTP_ACCEPTED,
        Response::HTTP_OK,
        Response::HTTP_CONTINUE,
        Response::HTTP_CREATED
    ];

    /**
     * @var int
     */
    private $code;

    /**
     * ApplicationResponse constructor.
     *
     * @param int $code
     * @param array $body
     */
    public function __construct(int $code, array $body)
    {
        $data = [];
        $this->code = $code;

        if ($this->isSuccessful()) {
            $data = [
                ApplicationResponseInterface::STATUS => $body['status'],
                ApplicationResponseInterface::EXTERNAL_ID => $body['externalId'],
                ApplicationResponseInterface::REDIRECT_URI => $body['applicationUrl'],
                ApplicationResponseInterface::HREF => $body['_links']['self']['href']
            ];
        }

        parent::__construct($data);
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @return string|null
     */
    public function getExternalId(): ?string
    {
        return $this->getData(self::EXTERNAL_ID);
    }

    /**
     * @return string|null
     */
    public function getRedirectUri(): ?string
    {
        return $this->getData(self::REDIRECT_URI);
    }

    /**
     * @return string|null
     */
    public function getHref(): ?string
    {
        return $this->getData(self::HREF);
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return in_array($this->code, self::POSITIVE_HTTP_CODES);
    }
}

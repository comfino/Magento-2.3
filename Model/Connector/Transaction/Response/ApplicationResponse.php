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
        Response::HTTP_CREATED,
    ];

    /**
     * @var int
     */
    private $code;

    /**
     * @var string
     */
    private $body;

    public function __construct(int $code, array $data, string $body)
    {
        $result = [];
        $this->code = $code;
        $this->body = $body;

        if ($this->isSuccessful()) {
            $result = [
                ApplicationResponseInterface::STATUS => $data['status'],
                ApplicationResponseInterface::EXTERNAL_ID => $data['externalId'],
                ApplicationResponseInterface::REDIRECT_URI => $data['applicationUrl'],
                ApplicationResponseInterface::HREF => $data['_links']['self']['href']
            ];
        }

        parent::__construct($result);
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }

    public function getExternalId(): ?string
    {
        return $this->getData(self::EXTERNAL_ID);
    }

    public function getRedirectUri(): ?string
    {
        return $this->getData(self::REDIRECT_URI);
    }

    public function getHref(): ?string
    {
        return $this->getData(self::HREF);
    }

    public function isSuccessful(): bool
    {
        return in_array($this->code, self::POSITIVE_HTTP_CODES, true);
    }
}

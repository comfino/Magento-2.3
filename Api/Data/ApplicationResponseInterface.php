<?php

namespace Comfino\ComfinoGateway\Api\Data;

interface ApplicationResponseInterface
{
    public const STATUS = "status";
    public const EXTERNAL_ID = "external_id";
    public const REDIRECT_URI = "redirect_uri";
    public const HREF = "href";
    public const ORDER_ID = "order_id";

    public function getBody(): string;

    public function getStatus(): ?string;

    public function getExternalId(): ?string;

    public function getRedirectUri(): ?string;

    public function getHref(): ?string;
}

<?php

namespace Comperia\ComperiaGateway\Api\Data;

interface ApplicationResponseInterface
{
    const STATUS = "status";
    const EXTERNAL_ID = "external_id";
    const REDIRECT_URI = "redirect_uri";
    const HREF = "href";
    const ORDER_ID = "order_id";

    /**
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @return string|null
     */
    public function getExternalId(): ?string;

    /**
     * @return string|null
     */
    public function getRedirectUri(): ?string;

    /**
     * @return string|null
     */
    public function getHref(): ?string;
}

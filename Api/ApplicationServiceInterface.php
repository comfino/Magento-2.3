<?php

namespace Comfino\ComfinoGateway\Api;

interface ApplicationServiceInterface
{
    /**
     * @return array
     */
    public function save(): array;

    /**
     * @param string $externalId
     * @param string $status
     * @return bool
     */
    public function changeStatus(string $externalId, string $status): bool;
}

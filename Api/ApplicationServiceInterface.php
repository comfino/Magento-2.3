<?php

namespace Comfino\ComfinoGateway\Api;

interface ApplicationServiceInterface
{
    /**
     * Creates an application in the Comfino API and returns the redirect URL.
     *
     * @return array
     */
    public function save(): array;
}

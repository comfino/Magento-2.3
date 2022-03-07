<?php

namespace Comfino\ComfinoGateway\Api;

interface ApplicationServiceInterface
{
    /**
     * @return array
     */
    public function save(): array;

    /**
     * @return void
     */
    public function changeStatus(): void;
}

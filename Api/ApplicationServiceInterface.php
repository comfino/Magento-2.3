<?php

namespace Comperia\ComperiaGateway\Api;

interface ApplicationServiceInterface
{
    /**
     * @return string
     */
    public function save(): string;

    /**
     * @return void
     */
    public function changeStatus(): void;
}

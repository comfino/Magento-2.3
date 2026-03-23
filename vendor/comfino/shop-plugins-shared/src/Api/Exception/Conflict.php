<?php

declare(strict_types=1);

namespace Comfino\Api\Exception;

class Conflict extends AccessDenied
{
    public function getStatusCode(): int
    {
        return 409;
    }
}

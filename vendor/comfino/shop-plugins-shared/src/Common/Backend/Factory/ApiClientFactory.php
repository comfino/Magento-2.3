<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Factory;

use Comfino\Common\Api\Client;

final class ApiClientFactory
{
    /**
     * @param string|null $apiKey
     * @param string|null $userAgent
     * @param string|null $apiHost
     * @param string|null $apiLanguage
     * @param int $connectionTimeout
     * @param int $transferTimeout
     * @param int $connectionMaxNumAttempts
     * @param array $curlOptions
     */
    public function createClient(
        ?string $apiKey,
        ?string $userAgent,
        ?string $apiHost = null,
        ?string $apiLanguage = null,
        int $connectionTimeout = 1,
        int $transferTimeout = 3,
        int $connectionMaxNumAttempts = 3,
        array $curlOptions = []
    ): Client {
        $client = new Client($apiKey, $connectionTimeout, $transferTimeout, $connectionMaxNumAttempts, $curlOptions);

        if ($userAgent !== null) {
            $client->setCustomUserAgent($userAgent);
        }

        if ($apiHost !== null) {
            $client->setCustomApiHost($apiHost);
        }

        if ($apiLanguage !== null) {
            $client->setApiLanguage($apiLanguage);
        }

        return $client;
    }
}

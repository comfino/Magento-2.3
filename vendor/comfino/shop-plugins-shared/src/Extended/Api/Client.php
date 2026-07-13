<?php

declare(strict_types=1);

namespace Comfino\Extended\Api;

use Comfino\Api\Response\Base as BaseApiResponse;
use Comfino\Api\SerializerInterface;
use Comfino\Extended\Api\Dto\Plugin\ShopPluginError;
use Comfino\Extended\Api\Request\ClaimErrorLoggingToken as ClaimErrorLoggingTokenRequest;
use Comfino\Extended\Api\Request\GetLatestPluginRelease as GetLatestPluginReleaseRequest;
use Comfino\Extended\Api\Request\GetSupportedPlatforms as GetSupportedPlatformsRequest;
use Comfino\Extended\Api\Request\NotifyAbandonedCart;
use Comfino\Extended\Api\Request\NotifyShopPluginRemoval;
use Comfino\Extended\Api\Request\ReportShopPluginError;
use Comfino\Extended\Api\Response\ClaimErrorLoggingToken as ClaimErrorLoggingTokenResponse;
use Comfino\Extended\Api\Response\GetLatestPluginRelease as GetLatestPluginReleaseResponse;
use Comfino\Extended\Api\Response\GetSupportedPlatforms as GetSupportedPlatformsResponse;
use Comfino\Extended\Api\Serializer\Json as JsonSerializer;
use ComfinoExternal\Psr\Http\Client\ClientInterface;
use ComfinoExternal\Psr\Http\Message\RequestFactoryInterface;
use ComfinoExternal\Psr\Http\Message\StreamFactoryInterface;

class Client extends \Comfino\Api\Client
{
    public function __construct(
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ClientInterface $client,
        ?string $apiKey,
        $apiVersion = 1,
        ?SerializerInterface $serializer = null
    ) {
        parent::__construct($requestFactory, $streamFactory, $client, $apiKey, $apiVersion, $serializer ?? new JsonSerializer());
    }

    /**
     * @param ShopPluginError $shopPluginError
     * @return bool
     */
    public function sendLoggedError($shopPluginError): bool
    {
        try {
            $request = new ReportShopPluginError($shopPluginError, $this->getUserAgent());

            new BaseApiResponse(
                $request,
                $this->sendRequest($request->setSerializer($this->serializer), 2),
                $this->serializer
            );
        } catch (\Throwable $exception) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function notifyPluginRemoval(): bool
    {
        try {
            $this->sendRequest((new NotifyShopPluginRemoval())->setSerializer($this->serializer));
        } catch (\Throwable $exception) {
            return false;
        }

        return true;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function notifyAbandonedCart($type): bool
    {
        try {
            $this->sendRequest((new NotifyAbandonedCart($type))->setSerializer($this->serializer));
        } catch (\Throwable $exception) {
            return false;
        }

        return true;
    }

    /**
     * @return ClaimErrorLoggingTokenResponse|null
     */
    public function claimErrorLoggingToken(): ?ClaimErrorLoggingTokenResponse
    {
        try {
            $this->request = (new ClaimErrorLoggingTokenRequest())->setSerializer($this->serializer);

            return new ClaimErrorLoggingTokenResponse($this->request, $this->sendRequest($this->request), $this->serializer);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * @return GetSupportedPlatformsResponse|null
     */
    public function getSupportedPlatforms(): ?GetSupportedPlatformsResponse
    {
        try {
            $this->request = (new GetSupportedPlatformsRequest())->setSerializer($this->serializer);

            return new GetSupportedPlatformsResponse($this->request, $this->sendRequest($this->request), $this->serializer);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * @param string $platform
     * @return GetLatestPluginReleaseResponse|null
     */
    public function getLatestPluginRelease($platform): ?GetLatestPluginReleaseResponse
    {
        try {
            $this->request = (new GetLatestPluginReleaseRequest($platform))->setSerializer($this->serializer);

            return new GetLatestPluginReleaseResponse($this->request, $this->sendRequest($this->request), $this->serializer);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}

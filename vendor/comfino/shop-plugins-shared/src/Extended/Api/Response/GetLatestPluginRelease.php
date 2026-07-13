<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Response;

use Comfino\Api\Response\Base;

class GetLatestPluginRelease extends Base
{
    /**
     * @var string
     */
    public $platform;

    /**
     * @var string
     */
    public $version;

    /**
     * @var string|null
     */
    public $downloadUrl;

    /**
     * @var string
     */
    public $releaseUrl;

    /**
     * @var bool
     */
    public $prerelease;

    /**
     * @var string|null
     */
    public $publishedAt;

    /**
     * @var string|null
     */
    public $minPlatformVersion;

    /**
     * @var string|null
     */
    public $minPhpVersion;

    /**
     * @var string|null
     */
    public $descriptionHtml;

    /**
     * @param mixed[]|string|bool|null|float|int $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
        $this->checkResponseType($deserializedResponseBody, 'array');
        $this->checkResponseStructure($deserializedResponseBody, ['platform', 'version', 'release_url']);

        $this->platform = $deserializedResponseBody['platform'];
        $this->version = $deserializedResponseBody['version'];
        $this->downloadUrl = $deserializedResponseBody['download_url'] ?? null;
        $this->releaseUrl = $deserializedResponseBody['release_url'];
        $this->prerelease = (bool) ($deserializedResponseBody['prerelease'] ?? false);
        $this->publishedAt = $deserializedResponseBody['published_at'] ?? null;
        $this->minPlatformVersion = $deserializedResponseBody['min_platform_version'] ?? null;
        $this->minPhpVersion = $deserializedResponseBody['min_php_version'] ?? null;
        $this->descriptionHtml = $deserializedResponseBody['description_html'] ?? null;
    }
}

<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\RestEndpoint;

use Comfino\Common\Backend\ConfigurationManager;
use Comfino\Common\Backend\DebugLogger;
use Comfino\Common\Backend\RestEndpoint;
use Comfino\Common\Exception\InvalidEndpoint;

class Configuration extends RestEndpoint
{
    /**
     * @var \Comfino\Common\Backend\ConfigurationManager
     */
    private $configurationManager;
    /**
     * @var \Comfino\Common\Backend\DebugLogger
     */
    private $debugLogger;
    /**
     * @var string
     */
    private $platformName;
    /**
     * @var string
     */
    private $platformVersion;
    /**
     * @var string
     */
    private $pluginVersion;
    /**
     * @var int
     */
    private $pluginBuildTs;
    /**
     * @var string
     */
    private $databaseVersion;
    /**
     * @var int
     */
    private $debugLogNumLines;
    /**
     * @var mixed[]|null
     */
    private $shopExtraVariables;
    public function __construct(
        string $name,
        string $endpointUrl,
        ConfigurationManager $configurationManager,
        DebugLogger $debugLogger,
        string $platformName,
        string $platformVersion,
        string $pluginVersion,
        int $pluginBuildTs,
        string $databaseVersion,
        int $debugLogNumLines,
        ?array $shopExtraVariables = null
    ) {
        $this->configurationManager = $configurationManager;
        $this->debugLogger = $debugLogger;
        $this->platformName = $platformName;
        $this->platformVersion = $platformVersion;
        $this->pluginVersion = $pluginVersion;
        $this->pluginBuildTs = $pluginBuildTs;
        $this->databaseVersion = $databaseVersion;
        $this->debugLogNumLines = $debugLogNumLines;
        $this->shopExtraVariables = $shopExtraVariables;
        parent::__construct($name, $endpointUrl);

        $this->methods = ['GET', 'POST', 'PUT', 'PATCH'];
    }

    /**
     * @param \ComfinoExternal\Psr\Http\Message\ServerRequestInterface $serverRequest
     * @param string|null $endpointName
     */
    public function processRequest($serverRequest, $endpointName = null): ?array
    {
        if (!$this->endpointPathMatch($serverRequest, $endpointName)) {
            throw new InvalidEndpoint('Endpoint path does not match request path.');
        }

        $shopExtraVariables = $this->shopExtraVariables !== null ? array_merge($this->shopExtraVariables) : null;

        if ($shopExtraVariables !== null && isset($shopExtraVariables['wordpress_version'])) {
            $wpVersion = $shopExtraVariables['wordpress_version'];
            unset($shopExtraVariables['wordpress_version']);
        } else {
            $wpVersion = 'n/a';
        }

        if (strtoupper($serverRequest->getMethod()) === 'GET') {
            $responseType = $serverRequest->getQueryParams()['responseType'] ?? 'configuration';

            if ($responseType === 'debug_log') {
                return ['debug_log' => $this->debugLogger->getDebugLog($this->debugLogNumLines)];
            }

            return [
                'shop_info' => [
                    'platform' => $this->platformName,
                    'platform_version' => $this->platformVersion,
                    'plugin_version' => $this->pluginVersion,
                    'plugin_build_ts' => $this->pluginBuildTs,
                    'wordpress_version' => $wpVersion,
                    'symfony_version' => class_exists('\Symfony\Component\HttpKernel\Kernel')
                        ? \ComfinoExternal\Symfony\Component\HttpKernel\Kernel::VERSION
                        : 'n/a',
                    'php_version' => PHP_VERSION,
                    'server_software' => $serverRequest->getServerParams()['SERVER_SOFTWARE'],
                    'server_name' => $serverRequest->getServerParams()['SERVER_NAME'],
                    'server_addr' => $serverRequest->getServerParams()['SERVER_ADDR'],
                    'database_version' => $this->databaseVersion,
                    'extra_variables' => $shopExtraVariables,
                ],
                'shop_configuration' => $this->configurationManager->returnConfigurationOptions(),
            ];
        }

        $this->configurationManager->updateConfigurationOptions(parent::processRequest($serverRequest, $endpointName));
        $this->configurationManager->persist();

        return null;
    }
}

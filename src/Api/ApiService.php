<?php

namespace Comfino\Api;

use Comfino\Common\Backend\Factory\ApiServiceFactory;
use Comfino\Common\Backend\RestEndpoint\CacheInvalidate;
use Comfino\Common\Backend\RestEndpoint\Configuration;
use Comfino\Common\Backend\RestEndpoint\StatusNotification;
use Comfino\Common\Backend\RestEndpointManager;
use Comfino\Common\Shop\Order\StatusManager;
use Comfino\ComfinoGateway\Helper\Data;
use Comfino\Configuration\ConfigManager;
use Comfino\DebugLogger;
use Comfino\Order\StatusAdapter;
use Comfino\PluginShared\CacheManager;
use ComfinoExternal\Psr\Http\Message\ResponseInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\OrderRepository;

/**
 * Central REST endpoint manager for module API.
 *
 * Handles:
 * - StatusNotification (webhook from Comfino -> order status update)
 * - Configuration (remote config management by Comfino admin panel)
 * - CacheInvalidate (cache clearing endpoint)
 */
final class ApiService
{
    private static ?RestEndpointManager $endpointManager = null;
    private static bool $initialized = false;

    public static function init(
        string $notificationUrl,
        string $configUrl,
        string $cacheInvalidateUrl,
        string $magentoVersion,
        string $pluginVersion,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ): void {
        if (self::$initialized) {
            return;
        }

        $endpointManager = self::getEndpointManager($magentoVersion, $pluginVersion);

        $endpointManager->registerEndpoint(
            new StatusNotification(
                'transactionStatus',
                $notificationUrl,
                StatusManager::getInstance(new StatusAdapter($orderRepository, $searchCriteriaBuilder)),
                ConfigManager::getForbiddenStatuses(),
                ConfigManager::getIgnoredStatuses()
            )
        );

        $endpointManager->registerEndpoint(
            new Configuration(
                'configuration',
                $configUrl,
                ConfigManager::getInstance(),
                DebugLogger::getLoggerInstance(),
                'Magento',
                $magentoVersion,
                $pluginVersion,
                Data::BUILD_TS,
                ConfigManager::getEnvironmentInfo(['database_version'])['database_version'],
                200
            )
        );

        $endpointManager->registerEndpoint(
            new CacheInvalidate(
                'cacheInvalidate',
                $cacheInvalidateUrl,
                CacheManager::getCachePool()
            )
        );

        self::$initialized = true;
    }

    /**
     * Lazy-initialize ApiService from Magento ObjectManager if not yet initialized. Safe to call multiple times.
     */
    public static function ensureInitialized(): void
    {
        if (self::$initialized) {
            return;
        }

        $om = ObjectManager::getInstance();

        /** @var Data $helper */
        $helper = $om->get(Data::class);

        /** @var UrlInterface $urlBuilder */
        $urlBuilder = $om->get(UrlInterface::class);

        /** @var DirectoryList $dirList */
        $dirList = $om->get(DirectoryList::class);

        /** @var OrderRepository $orderRepository */
        $orderRepository = $om->get(OrderRepository::class);

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $om->get(SearchCriteriaBuilder::class);

        CacheManager::init($dirList->getPath('var'));

        self::init(
            $urlBuilder->getUrl('comfino/api/transactionstatus'),
            $urlBuilder->getUrl('comfino/api/configuration'),
            $urlBuilder->getUrl('comfino/api/cacheinvalidate'),
            $helper->getShopVersion(),
            $helper->getModuleVersion(),
            $orderRepository,
            $searchCriteriaBuilder
        );
    }

    /**
     * Process a named endpoint request.
     * Returns PSR-7 ResponseInterface from RestEndpointManager.
     */
    public static function processRequest(string $endpointName): ResponseInterface
    {
        self::ensureInitialized();

        if (ConfigManager::isDebugMode()) {
            $request = self::$endpointManager->getServerRequest();

            DebugLogger::logEvent(
                '[REST API request]',
                'processRequest',
                [
                    '$endpointName' => $endpointName,
                    'METHOD'  => $request->getMethod(),
                    'PARAMS'  => $request->getQueryParams(),
                    'HEADERS' => $request->getHeaders(),
                    'BODY'    => $request->getBody()->getContents(),
                ]
            );
        }

        $response = self::$endpointManager->processRequest($endpointName);

        if (ConfigManager::isDebugMode() && $response->getStatusCode() !== 200) {
            DebugLogger::logEvent(
                '[REST API response]',
                'processRequest',
                [
                    '$endpointName'            => $endpointName,
                    'RECEIVED-CR-SIGNATURE'    => self::$endpointManager->getReceivedCrSignature(),
                    'CALCULATED-CR-SIGNATURE'  => self::$endpointManager->getCalculatedCrSignature(),
                    'HEADERS'                  => $response->getHeaders(),
                    'STATUS'                   => $response->getStatusCode(),
                    'BODY'                     => $response->getBody()->getContents(),
                ]
            );
        }

        return $response;
    }

    private static function getEndpointManager(string $magentoVersion, string $pluginVersion): RestEndpointManager
    {
        if (self::$endpointManager === null) {
            self::$endpointManager = (new ApiServiceFactory())->createService(
                'Magento',
                $magentoVersion,
                $pluginVersion,
                [
                    ConfigManager::getConfigurationValue('COMFINO_API_KEY'),
                    ConfigManager::getConfigurationValue('COMFINO_SANDBOX_API_KEY'),
                ]
            );
        }

        return self::$endpointManager;
    }
}

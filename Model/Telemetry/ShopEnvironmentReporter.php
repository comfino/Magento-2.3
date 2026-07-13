<?php

/**
 * Comfino Payment Gateway for Magento 2
 *
 * @package Comfino\ComfinoGateway\Model\Telemetry
 * @author Artur Kozubski <akozubski@comperia.pl>
 * @copyright Copyright (c) 2026 Comfino by Comperia.pl S.A.
 * @license https://opensource.org/licenses/OSL-3.0 Open Software License 3.0
 * @link https://github.com/comfino/magento2
 */

declare(strict_types=1);

namespace Comfino\ComfinoGateway\Model\Telemetry;

use Comfino\Api\ApiClient;
use Comfino\DebugLogger;
use Comfino\Frontend\AbstractShopEnvironmentBuilder;
use Throwable;

/**
 * Fire-and-forget service that reports the full shop environment to the Comfino API.
 *
 * The report carries platform/plugin versions, edition, theme metadata, and capability hints that belong
 * server-to-server only. Failures are logged via the debug logger and never propagated — this service must not impact
 * paywall or widget functionality.
 */
class ShopEnvironmentReporter
{
    /**
     * @var AbstractShopEnvironmentBuilder
     */
    private $shopEnvironmentBuilder;

    /**
     * @var TestProductUrlResolver
     */
    private $testProductUrlResolver;

    public function __construct(
        AbstractShopEnvironmentBuilder $shopEnvironmentBuilder,
        TestProductUrlResolver $testProductUrlResolver
    ) {
        $this->shopEnvironmentBuilder = $shopEnvironmentBuilder;
        $this->testProductUrlResolver = $testProductUrlResolver;
    }

    /**
     * Sends the current shop environment report to the Comfino API.
     *
     * @return bool True if the report was accepted, false on any failure.
     */
    public function report(): bool
    {
        try {
            $testProductUrl = $this->testProductUrlResolver->resolve();
            $report = $this->shopEnvironmentBuilder->buildForBackendReport($testProductUrl);

            $result = ApiClient::getInstance()->reportShopEnvironment($report);

            DebugLogger::logEvent(
                '[SHOP_ENVIRONMENT]',
                'ShopEnvironmentReporter::report: ' . ($result ? 'accepted' : 'rejected by API')
            );

            return $result;
        } catch (Throwable $e) {
            DebugLogger::logEvent(
                '[SHOP_ENVIRONMENT]',
                'ShopEnvironmentReporter::report: failed',
                ['exceptionMessage' => $e->getMessage()]
            );

            return false;
        }
    }

    /**
     * Builds the current shop environment report as an array, for on-demand exposure via the configuration endpoint.
     *
     * @return array<string, mixed>|null The report array, or null on failure.
     */
    public function getReportArray(): ?array
    {
        try {
            $testProductUrl = $this->testProductUrlResolver->resolve();

            return $this->shopEnvironmentBuilder->buildReportArray($testProductUrl);
        } catch (Throwable $e) {
            DebugLogger::logEvent(
                '[SHOP_ENVIRONMENT]',
                'ShopEnvironmentReporter::getReportArray: failed',
                ['exceptionMessage' => $e->getMessage()]
            );

            return null;
        }
    }
}
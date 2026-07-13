<?php

declare(strict_types=1);

namespace Comfino\ComfinoGateway\Observer;

use Comfino\ComfinoGateway\Model\Telemetry\ShopEnvironmentReporter;
use Comfino\Configuration\ConfigManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Triggers a shop environment report to the Comfino API whenever the admin saves the payment section.
 *
 * This refreshes the API-side knowledge base when the merchant changes the API key, sandbox toggle, or any other
 * configuration that might reflect a theme or platform change. Only fires when an API key is configured, since the
 * report requires authenticated API access. Fire-and-forget: any failure is swallowed inside ShopEnvironmentReporter.
 */
class AdminConfigSaveObserver implements ObserverInterface
{
    /**
     * @var ShopEnvironmentReporter
     */
    private $shopEnvironmentReporter;

    public function __construct(ShopEnvironmentReporter $shopEnvironmentReporter)
    {
        $this->shopEnvironmentReporter = $shopEnvironmentReporter;
    }

    public function execute(Observer $observer): void
    {
        if (!empty(ConfigManager::getApiKey())) {
            $this->shopEnvironmentReporter->report();
        }
    }
}

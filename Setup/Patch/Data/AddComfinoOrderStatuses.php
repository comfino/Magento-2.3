<?php

namespace Comfino\ComfinoGateway\Setup\Patch\Data;

use Comfino\Common\Shop\Order\StatusManager;
use Comfino\Common\Shop\OrderStatusAdapterInterface;
use Comfino\Order\ShopStatusManager;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

// When the module lives under app/code/, Magento's autoloader does not honour the
// "Comfino\": "src/" PSR-4 mapping from the module's own composer.json.
// Manually load the required class files so they are available regardless of how
// the module is deployed (app/code/ copy vs. Composer vendor/ install).
// class_exists() guards prevent fatal "cannot redeclare" errors when Composer's
// autoloader has already resolved the class from vendor/.
(static function (): void {
    $moduleRoot = dirname(__DIR__, 3);
    $sharedLibRoot = $moduleRoot . '/vendor/comfino/shop-plugins-shared/src/Common/Shop';

    if (!interface_exists(OrderStatusAdapterInterface::class, false)) {
        require_once $sharedLibRoot . '/OrderStatusAdapterInterface.php';
    }

    if (!class_exists(StatusManager::class, false)) {
        require_once $sharedLibRoot . '/Order/StatusManager.php';
    }

    if (!class_exists(ShopStatusManager::class, false)) {
        require_once $moduleRoot . '/src/Order/ShopStatusManager.php';
    }
})();

class AddComfinoOrderStatuses implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $statusTable = $this->moduleDataSetup->getTable('sales_order_status');
        $statusStateTable = $this->moduleDataSetup->getTable('sales_order_status_state');

        foreach (ShopStatusManager::CUSTOM_STATUS_LABELS as $statusCode => $details) {
            // Insert into sales_order_status only if code does not exist yet.
            $existing = $connection->fetchOne(
                $connection->select()
                    ->from($statusTable, ['status'])
                    ->where('status = ?', $statusCode)
            );

            if (!$existing) {
                $connection->insert($statusTable, [
                    'status' => $statusCode,
                    'label' => $details['label'],
                ]);
            }

            // Assign to state only if not already assigned.
            $assignedState = $connection->fetchOne(
                $connection->select()
                    ->from($statusStateTable, ['state'])
                    ->where('status = ?', $statusCode)
                    ->where('state = ?', $details['state'])
            );

            if (!$assignedState) {
                $connection->insert($statusStateTable, [
                    'status' => $statusCode,
                    'state' => $details['state'],
                    'is_default' => 0,
                    'visible_on_front' => 1,
                ]);
            }
        }

        $connection->endSetup();

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
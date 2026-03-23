<?php

namespace Comfino\ComfinoGateway\Setup;

use Comfino\Common\Shop\Order\StatusManager;
use Comfino\Common\Shop\OrderStatusAdapterInterface;
use Comfino\Order\ShopStatusManager;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

// Same autoloader workaround as in Setup\Patch\Data\AddComfinoOrderStatuses —
// app/code/ installs bypass the module's PSR-4 mappings for the Comfino\ bridge namespace.
(static function (): void {
    $moduleRoot = dirname(__DIR__);
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

class Uninstall implements UninstallInterface
{
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $connection = $setup->getConnection();

        $setup->startSetup();

        $statusCodes = array_keys(ShopStatusManager::CUSTOM_STATUS_LABELS);
        $statusTable = $setup->getTable('sales_order_status');
        $statusStateTable = $setup->getTable('sales_order_status_state');
        $orderTable = $setup->getTable('sales_order');
        $historyTable = $setup->getTable('sales_order_status_history');

        // Always unassign from states - prevents future use regardless of history.
        $connection->delete(
            $statusStateTable,
            ['status IN (?)' => $statusCodes]
        );

        // Hard-delete from sales_order_status only for statuses that no order references.
        foreach ($statusCodes as $statusCode) {
            $usedInOrders = (int) $connection->fetchOne(
                $connection->select()
                    ->from($orderTable, [new \Zend_Db_Expr('COUNT(*)')])
                    ->where('status = ?', $statusCode)
            );

            $usedInHistory = (int) $connection->fetchOne(
                $connection->select()
                    ->from($historyTable, [new \Zend_Db_Expr('COUNT(*)')])
                    ->where('status = ?', $statusCode)
            );

            if ($usedInOrders === 0 && $usedInHistory === 0) {
                $connection->delete($statusTable, ['status = ?' => $statusCode]);
            }
            // If still in use: leave row in sales_order_status so historical labels remain visible.
        }

        $setup->endSetup();
    }
}
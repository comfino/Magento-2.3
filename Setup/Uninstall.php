<?php

namespace Comfino\ComfinoGateway\Setup;

use Comfino\Order\ShopStatusManager;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

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

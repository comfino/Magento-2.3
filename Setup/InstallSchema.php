<?php

namespace Comperia\ComperiaGateway\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema
 *
 * @package Comperia\ComperiaGateway\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface   $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $table = $setup->getConnection()
                       ->newTable($setup->getTable('comperia_application'))
                       ->addColumn(
                           'id',
                           Table::TYPE_INTEGER,
                           null,
                           [
                               'identity' => true,
                               'unsigned' => true,
                               'nullable' => false,
                               'primary'  => true,
                           ]
                       )
                       ->addColumn(
                           'status',
                           Table::TYPE_TEXT,
                           10,
                           [
                               'nullable' => false,
                           ]
                       )
                       ->addColumn(
                           'external_id',
                           Table::TYPE_TEXT,
                           32,
                           [
                               'nullable' => false,
                           ]
                       )
                       ->addColumn(
                           'redirect_uri',
                           Table::TYPE_TEXT,
                           null,
                           [
                               'nullable' => false,
                           ]
                       )
                       ->addColumn(
                           'href',
                           Table::TYPE_TEXT,
                           null,
                           [
                               'nullable' => false,
                           ]
                       )
                       ->addColumn(
                           'created_at',
                           Table::TYPE_TEXT,
                           60,
                           [
                               'nullable' => false,
                           ]
                       )
                       ->addColumn(
                           'updated_at',
                           Table::TYPE_TEXT,
                           60,
                           [
                               'nullable' => false,
                           ]
                       )
                       ->addColumn(
                           'order_id',
                           Table::TYPE_TEXT,
                           null,
                           [
                               'nullable' => false,
                           ]
                       );
        $setup->getConnection()
              ->createTable($table);
        $setup->endSetup();
    }
}

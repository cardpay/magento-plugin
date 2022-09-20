<?php

namespace Cardpay\Core\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 *
 * @package Cardpay\Core\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $salesTable = $installer->getTable('sales_order');
        $quoteTable = $installer->getTable('quote');

        $columns = [
            'finance_cost_amount' => [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => true,
                'comment' => 'Finance Cost Amount',
            ],
            'base_finance_cost_amount' => [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => true,
                'comment' => 'Base Finance Cost Amount',
            ]
        ];

        $connection = $installer->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($salesTable, $name, $definition);
            $connection->addColumn($quoteTable, $name, $definition);
        }

        $installer->endSetup();
    }
}
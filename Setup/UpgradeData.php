<?php

namespace Dialcom\Przelewy\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.1.13') < 0) {
            $installer = $setup;
            $installer->startSetup();

            $orderTable = $installer->getTable('sales_order');
            $columns = [
                'p24_session_id' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'p24_session_id',
                ],
            ];

            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($orderTable, $name, $definition);
            }
            $installer->endSetup();
        }
    }
}

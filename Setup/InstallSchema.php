<?php

namespace Dialcom\Przelewy\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (!$installer->tableExists('p24_recurring')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('p24_recurring'));
            $table->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'auto_increment' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ]
            )
                ->addColumn(
                    'customer',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                    ]
                )
                ->addColumn(
                    'reference',
                    Table::TYPE_TEXT,
                    64,
                    [
                        'nullable' => false,
                    ]
                )
                ->addColumn(
                    'expires',
                    Table::TYPE_TEXT,
                    4,
                    [
                        'nullable' => false,
                    ]
                )
                ->addColumn(
                    'mask',
                    Table::TYPE_TEXT,
                    32,
                    [
                        'nullable' => false,
                    ]
                )
                ->addColumn(
                    'card_type',
                    Table::TYPE_TEXT,
                    '255',
                    [
                        'nullable' => false,
                    ]
                )
                ->addColumn(
                    'timestamp',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'default' => 'CURRENT_TIMESTAMP',
                    ]
                )
                ->addIndex(
                    $installer->getIdxName(
                        'przelewy_recurring',
                        [
                            'mask',
                            'card_type',
                            'expires',
                            'customer'
                        ],
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    [
                        'mask',
                        'card_type',
                        'expires',
                        'customer'
                    ],
                    [
                        'type' => AdapterInterface::INDEX_TYPE_UNIQUE
                    ]
                )
                ->setComment('Recurring Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}

<?php

namespace Dialcom\Przelewy\Setup;

use Dialcom\Przelewy\Przelewy24Class;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Sales\Model\Order;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.1.28') < 0) {
            $objectManager = ObjectManager::getInstance();
            $status = $objectManager->create('Magento\Sales\Model\Order\Status');
            $status->setData('status', Przelewy24Class::PENDING_PAYMENT_CUSTOM_STATUS)->setData('label', 'Pending Payment P24')->save();
            $status->assignState(Order::STATE_NEW, false, true);
        }

        $setup->endSetup();
    }
}

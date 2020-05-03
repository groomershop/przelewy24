<?php

namespace Dialcom\Przelewy\Controller\Adminhtml\Przelewy;

class PaymentIvr extends \Magento\Backend\App\Action
{
    /**
     * PaymentIvr constructor.
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context
    )
    {
        parent::__construct($context);
    }


    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order_id = (int)$this->getRequest()->getParam('order_id');
        $order = $objectManager->create('Magento\Sales\Model\Order')->load($order_id);

        try {
            $channels = $objectManager->create('Dialcom\Przelewy\Model\Config\Channels');
            $response = $channels->runIvrPayment($order);
            $this->messageManager->addSuccess($response);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        $this->_redirect('sales/order/view', array('order_id' => $order->getId()));
        return;
    }
}

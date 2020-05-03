<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

class ReturnUrl extends \Magento\Framework\App\Action\Action
{
    /**
     * ReturnUrl constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    )
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $requestParams = $this->getRequest()->getParams();
        $orderId = (int) $requestParams['ga_order_id'];
        
        if (!is_null($orderId) && $orderId > 1) {
            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
            if ($order->getState() === \Magento\Sales\Model\Order::STATE_PROCESSING) {
                $this->_redirect('przelewy/przelewy/success', $requestParams);
            } else {
                $this->_redirect('przelewy/przelewy/failure',$requestParams);
            }
        } else {
            $requestParams['ga_order_id'] = 0;
            $this->_redirect('przelewy/przelewy/failure',$requestParams);
        }
    }
}

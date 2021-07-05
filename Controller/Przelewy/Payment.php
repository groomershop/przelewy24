<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

class Payment extends \Magento\Framework\App\Action\Action
{
    /**
     * Payment constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        return;
        //TODO - not working - this is ga_before_payment feature

        $order_id = $this->_objectManager->get('Magento\Checkout\Model\Session')->getLastRealOrderId();

        if ($order_id) {
            $this->getResponse()->setBody(
                $this->_view->getLayout()->createBlock('Dialcom\Przelewy\Block\Payment\Przelewy\Redirect')->getHtml()
            );
        }
    }
}

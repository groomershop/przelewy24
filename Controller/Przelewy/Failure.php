<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

class Failure extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Dialcom\Przelewy\Helper\Data
     */
    protected $helper;

    /**
     * Failure constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Dialcom\Przelewy\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Dialcom\Przelewy\Helper\Data $helper
    )
    {
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        $requestParams = $this->getRequest()->getParams();
        $session = $this->_objectManager->get('Magento\Checkout\Model\Session');
        $gaOrderId = isset($requestParams['ga_order_id']) ? $requestParams['ga_order_id'] : 0;
        $order_id = $gaOrderId ? $requestParams['ga_order_id'] : $session->getLastRealOrderId();
        $session->getQuote()->setIsActive(false)->save();
        $this->messageManager->addSuccessMessage(__("Your payment was not confirmed by Przelewy24. Contact with your seller for more information."));

        if (is_null($order_id) || $order_id) {
            $this->_redirect('checkout/onepage/success', array('ga_order_id' => 0));
        } else {
            $this->_redirect('checkout/onepage/success', array('ga_order_id' => $this->helper->getGaOrderId($order_id)));
        }
    }
}

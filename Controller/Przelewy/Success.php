<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Dialcom\Przelewy\Helper\Data
     */
    protected $helper;

    /**
     * Success constructor.
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
        $externalOrderId = $requestParams['ga_order_id'];

        if (is_null($externalOrderId) || $externalOrderId) {
            $requestParams['ga_order_id'] = 0;
            $this->_redirect('przelewy/przelewy/failure',$requestParams);
        } else {
            $ga_order_id = $this->helper->getGaOrderId($externalOrderId);
            $session->getQuote()->setIsActive(false)->save();
            $this->_redirect('checkout/onepage/success', array('ga_order_id' => $ga_order_id));
        }
    }
}

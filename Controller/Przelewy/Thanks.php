<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

class Thanks extends \Magento\Framework\App\Action\Action
{
    /**
     * Thanks constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    )
    {
        parent::__construct($context);
    }


    /**
     * @return void
     */
    public function execute()
    {
        $this->messageManager->addSuccess(__('Thank you for your payment.'));
        $this->_redirect('checkout/cart');
    }
}

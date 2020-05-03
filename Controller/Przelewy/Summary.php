<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

use Dialcom\Przelewy\Helper\Data;
use Dialcom\Przelewy\Model\Config\Waluty;

class Summary extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Summary constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }


    /**
     * @return void
     */
    public function execute()
    {
        $key = $this->getRequest()->getParam('key');
        $order_id = (int)$this->getRequest()->getParam('order_id');
        $_order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($order_id);
        $store_id = $_order->getStoreId();
        $fullConfig = Waluty::getFullConfig($_order->getOrderCurrencyCode(), $this->scopeConfig, $store_id);

        $right_key = md5($store_id . '|' . $_order->getIncrementId());

        if (!$_order || $_order->getBaseTotalDue() == 0 || $key !== $right_key) {
            $this->_redirect('customer/account');
        }

        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->set( __('Przelewy24 - continue order payment'));
        $this->_view->renderLayout();
    }
}

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
        $order_id = (string) $this->getRequest()->getParam('order_id');
        $_order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($order_id);
        $store_id = $_order->getStoreId();

        $right_key = md5($store_id . '|' . $_order->getEntityId());

        if (!$_order || $_order->getBaseTotalDue() == 0 || $key !== $right_key) {
            $this->_redirect('customer/account');
        }

        /* If the user is on this page, we assume the order is to be paid again. Clear p24_session_id. */
        $_order->setData('p24_session_id', '');
        $_order->save();

        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->set( __('Przelewy24 - continue order payment'));
        $this->_view->renderLayout();
    }

    /**
     * Get summary link.
     *
     * @param $storeId
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public static function getLink(\Magento\Sales\Model\Order $order): string
    {
        $storeId = $order->getStoreId();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $base_url = $storeManager->getStore($storeId)->getBaseUrl();

        /* StoreId jest pobierane bezposrednio z zamówienia dzięki czemu możemy skojazyć w jakim języku zamówienie zostało złożone */
        $right_key = md5($storeId . '|' . $order->getEntityId());

        $payment_link = $base_url . 'przelewy/przelewy/summary/order_id/' . $order->getIncrementId() . '/key/' . $right_key;

        return $payment_link;
    }
}

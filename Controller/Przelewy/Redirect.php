<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

use Dialcom\Przelewy\Helper\Data;
use Dialcom\Przelewy\Model\Recurring;

class Redirect extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Redirect constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $registry
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->registry = $registry;
        parent::__construct($context);
    }


    /**
     * @return void
     */
    public function execute()
    {
        $session = $this->_objectManager->get('Magento\Checkout\Model\Session');
        $session->setPrzelewyQuoteId($session->getQuoteId());

        $order_id = (int) $session->getLastRealOrderId();

        if ($order_id) {
            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($order_id);
            $paymentData = $order->getPayment()->getData();
            $additionalInformation = $paymentData['additional_information'];
            if(isset($additionalInformation['cc_id'])){
                $ccId = $additionalInformation['cc_id'];
            }
            else{
                $ccId = '';
            }
            if(isset($additionalInformation['p24_forget']))
            {
                Recurring::setP24Forget((int)$additionalInformation['p24_forget'] === 1);
            }
            if (isset($ccId) && (int)$ccId > 0) { // recurring
                $this->_redirect('przelewy/przelewy/oneClick');
            } else {
                $this->proceedRedirect($order_id, $additionalInformation);
            }
            $session->unsQuoteId();
        }
    }

    private function proceedRedirect($order_id, $additionalInformation)
    {
        $order_id = (int) $order_id;
        $payment = $this->_objectManager->create('Dialcom\Przelewy\Model\Payment\Przelewy');
        $payment->addExtracharge($order_id);
        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($order_id);
        $storeId = $order->getStoreId();

        $payinshop = (int)$this->scopeConfig->getValue(Data::XML_PATH_PAYINSHOP, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $gaBeforePayment = (int)$this->scopeConfig->getValue(Data::XML_PATH_GA_BEFORE_PAYMENT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

        if ($payinshop && in_array($additionalInformation['method_id'], Recurring::getChannelsCards())) {
            $this->_view->loadLayout();
            $this->_view->getPage()->getConfig()->getTitle()->set( __('Payment by card'));
            $this->registry->register('payInShopByCard', true);
            $this->_view->renderLayout();
        } else {
            if ($gaBeforePayment === 1) {
                $this->_view->loadLayout();
                $this->_view->renderLayout();
            } else {
                $this->getResponse()->setBody(
                    $this->_view->getLayout()->createBlock('Dialcom\Przelewy\Block\Payment\Przelewy\Redirect')->getHtml($order_id)
                );
            }
        }
    }
}

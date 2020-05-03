<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

use Dialcom\Przelewy\Helper\Data;
use Dialcom\Przelewy\Model\Config\Waluty;

class PaymentLink extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * PaymentLink constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        return;
        //TODO - not working - this is ga_before_payment feature

        $order_id = (int)$this->getRequest()->getParam('order_id');

        if ($order_id) {
            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($order_id);
            $storeId = $order->getStoreId();
            try {
                $templateId = (int)$this->scopeConfig->getValue(Data::XML_PATH_SENDLINK_MAILTEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
                if ($templateId > 0) {
                    $this->proceed($templateId, $order);
                }
            } catch (\Exception $e) {
                $this->messageManager->addError(__('Failed to send the payment email.'));
                $this->logger->critical($e);
            }

            $this->_redirect('/');
        }
        return;
    }

    private function proceed($templateId, \Magento\Sales\Model\Order $order)
    {
        $emailTemplate = $this->_objectManager->create('Magento\Email\Model\Template')->load($templateId);
        $storeId = $this->storeManager->getStore()->getId();
        $fullConfig = Waluty::getFullConfig($order->getOrderCurrencyCode(), $this->scopeConfig, $storeId);

        $right_key = md5($fullConfig['merchant_id'] . '|' . $order->getIncrementId());

        $order->payment_link = $this->_url->getUrl('przelewy/przelewy/summary', array('order_id' => $order->getIncrementId(), 'key' => $right_key));
        $vars = array('order' => $order);

        $receiveEmail = $order->getCustomerEmail();
        $receiveName = $order->getBillingAddress()->getData('firstname') . ' ' . $order->getBillingAddress()->getData('lastname');

        $emailTemplate->setSenderEmail($this->scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId));
        $emailTemplate->setSenderName($this->scopeConfig->getValue('trans_email/ident_general/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId));
        $emailTemplate->send($receiveEmail, $receiveName, $vars);
        $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, __('The link for payment with Przelewy24 has been sent by e-mail'));
        $order->save();

        $this->messageManager->addSuccess(__('The link for payment with Przelewy24 has been sent by e-mail'));
    }
}

<?php

namespace Dialcom\Przelewy\Controller\Adminhtml\Przelewy;

use Dialcom\Przelewy\Helper\Data;
use Dialcom\Przelewy\Model\Config\Waluty;

class PaymentEmail extends \Magento\Backend\App\Action
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
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var  \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * PaymentEmail constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->urlBuilder = $context->getUrl();
        $this->transportBuilder = $transportBuilder;

        parent::__construct($context);
    }

    public function execute()
    {
        $order_id = (int)$this->getRequest()->getParam('order_id');
        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($order_id);
        $storeId = $order->getStoreId();
        try {
            $template = $this->scopeConfig->getValue(Data::XML_PATH_SENDLINK_MAILTEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
            if (!empty($template)) {
                $this->proceed($template, $order);
            }
            $this->messageManager->addSuccess(__('The link for payment with Przelewy24 has been sent by e-mail!'));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addError(__('The link for payment with Przelewy24 has not been sent!'));
        }

        $this->_redirect('sales/order/view', array('order_id' => $order->getId()));

        return;
    }

    private function proceed($template, \Magento\Sales\Model\Order $order)
    {
        $storeId = $order->getStoreId();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $base_url = $storeManager->getStore($storeId)->getBaseUrl();

        /*StoreId jest pobierane bezposrednio z zamówienia dzięki czemu możemy skojazyć w jakim języku zamówienie zostało złożone */
        $right_key = md5($storeId . '|' . $order->getIncrementId());

        $payment_link = $base_url.'przelewy/przelewy/summary/order_id/' . $order->getIncrementId() . '/key/' . $right_key;
        $customerName = $order->getBillingAddress()->getData('firstname') . ' ' . $order->getBillingAddress()->getData('lastname');
        $order->customer_name = $customerName;
        $order->payment_link = $payment_link;

        $vars = array('order' => $order, 'payment_link' => $payment_link );
        $sender =
            array(
                'email' => $this->scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId),
                'name' => $this->scopeConfig->getValue('trans_email/ident_general/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId)
            );

        $transport = $this->transportBuilder->setTemplateIdentifier($template)
            ->setTemplateOptions(array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId))
            ->setTemplateVars($vars)
            ->setFrom($sender)
            ->addTo($order->getCustomerEmail(), $customerName)
            ->getTransport();
     
        $transport->sendMessage();

        $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, __('The link for payment with Przelewy24 has been sent by e-mail'));
        $order->save();
    }
}

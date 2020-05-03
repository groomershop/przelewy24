<?php

namespace Dialcom\Przelewy\Helper;

use Dialcom\Przelewy\Model\Recurring;
use Dialcom\Przelewy\Przelewy24Class;
use Dialcom\Przelewy\Model\Config\Waluty;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_TITLE = 'payment/dialcom_przelewy/title';
    const XML_PATH_TEXT = 'payment/dialcom_przelewy/text';
    const XML_PATH_MERCHANT_ID = 'payment/dialcom_przelewy/merchant_id';
    const XML_PATH_SHOP_ID = 'payment/dialcom_przelewy/shop_id';
    const XML_PATH_SALT = 'payment/dialcom_przelewy/salt';
    const XML_PATH_MIN_ORDER_TOTAL = 'payment/dialcom_przelewy/min_order_total';
    const XML_PATH_MAX_ORDER_TOTAL = 'payment/dialcom_przelewy/max_order_total';
    const XML_PATH_MODE = 'payment/dialcom_przelewy/mode';
    const XML_PATH_API_KEY = 'przelewy_settings/keys/api_key';
    const XML_PATH_GA_KEY = 'przelewy_settings/keys/ga_key';
    const XML_PATH_ONECLICK = 'przelewy_settings/oneclick/oneclick';
    const XML_PATH_PAYINSHOP = 'przelewy_settings/oneclick/payinshop';
    const XML_PATH_SHOWPAYMENTMETHODS = 'przelewy_settings/paymethods/showpaymethods';
    const XML_PATH_PAYMETHOD_FIRST = 'przelewy_settings/paymethods/paymethod_first';
    const XML_PATH_PAYMETHOD_SECOND = 'przelewy_settings/paymethods/paymethod_second';
    const XML_PATH_PAYMETHOD_ALL = 'przelewy_settings/paymethods/paymethods_all';
    const XML_PATH_SHOW_PROMOTED = 'przelewy_settings/promoted/show_promoted';
    const XML_PATH_PAYMETHOD_PROMOTED = 'przelewy_settings/promoted/paymethod_promoted';
    const XML_PATH_USEGRAPHICAL = 'przelewy_settings/paysettings/usegraphical';
    const XML_PATH_P24REGULATIONS = 'przelewy_settings/paysettings/p24regulations';
    const XML_PATH_INSTALLMENT = 'przelewy_settings/paysettings/installment';
    const XML_PATH_PAYSLOW = 'przelewy_settings/paysettings/payslow';
    const XML_PATH_TIMELIMIT = 'przelewy_settings/paysettings/timelimit';
    const XML_PATH_SENDLINK_MAILTEMPLATE = 'przelewy_settings/paysettings/sendlink_mailtemplate';
    const XML_PATH_IVR = 'przelewy_settings/paysettings/ivr';
    const XML_PATH_CHG_STATE = 'przelewy_settings/paysettings/chg_state';
    const XML_PATH_MK_INVOICE = 'przelewy_settings/paysettings/mk_invoice';
    const XML_PATH_WAIT_FOR_RESULT = 'przelewy_settings/paysettings/wait_for_result';
    const XML_PATH_EXTRACHARGE = 'przelewy_settings/additionall/extracharge';
    const XML_PATH_EXTRACHARGE_PRODUCT = 'przelewy_settings/additionall/extracharge_product';
    const XML_PATH_EXTRACHARGE_AMOUNT = 'przelewy_settings/additionall/extracharge_amount';
    const XML_PATH_EXTRACHARGE_PERCENT = 'przelewy_settings/additionall/extracharge_percent';
    const XML_PATH_GA_GROSS_PRICE = 'przelewy_settings/additionall/ga_gross_price';
    const XML_PATH_GA_BEFORE_PAYMENT = 'przelewy_settings/additionall/ga_before_payment';
    const XML_PREFIX_MULTICURR = 'przelewy_settings/multicurr/multicurr_'; // początek ścieżki

    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    protected $formFactory;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->formFactory = $formFactory;
        $this->objectManager = $objectManager;
        parent::__construct($context);
    }

    /**
     * Generate sessionId to use in P24 server
     *
     * @param int $orderId
     * @return string
     */
    public static function getSessionId($orderId)
    {
        return substr(
            $orderId . '|' . md5(uniqid(mt_rand(), true) . ':' . microtime(true)),
            0,
            100
        );
    }

    public function getConfig($configPath)
    {
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getRequestParam($param, $default = 0)
    {
        return $this->_getRequest()->getParam($param, $default);
    }

    public function getStoreName()
    {
        return $this->scopeConfig->getValue(
            'general/store_information/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getFormFactory()
    {
        return $this->formFactory->create();
    }

    public function makeInvoiceFromOrder($order)
    {
        try {
            if ($order->canInvoice()) {
                $invoice = $order->prepareInvoice();
                if ($invoice->getTotalQty()) {
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                    $invoice->register();
                    $transactionSave = $this->objectManager->create('Magento\Framework\DB\Transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                    $transactionSave->save();
                }
            }
        } catch (\Exception $e) {
            error_log(__METHOD__ . ' ' . $e->getMessage());
        }
    }

    public function verifyTransaction($order_id)
    {
        $order_id = (int) $order_id;
        $order = $this->objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($order_id);
        $payment = $order->getPayment();
        $storeId = $order->getStoreId();
        if ($payment) {
            $payment->setData('transaction_id', (int)$this->_getRequest()->getPost('p24_order_id'));
            $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);
        }
        $fullConfig = Waluty::getFullConfig($order->getOrderCurrencyCode(), $this->scopeConfig, $storeId);
        $P24 = new Przelewy24Class(
            $fullConfig['merchant_id'],
            $fullConfig['shop_id'],
            $fullConfig['salt'],
            ($this->scopeConfig->getValue(Data::XML_PATH_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId) == '1')
        );

        $currency = $order->getOrderCurrencyCode();

        $ret = $P24->trnVerifyEx(array('p24_amount' => number_format($order->getGrandTotal() * 100, 0, "", ""), 'p24_currency' => $currency));

        if ($ret !== null) {
            $sendOrderUpdateEmail = false;

            if ($ret === true) {
                $chgState = (int)$this->scopeConfig->getValue(Data::XML_PATH_CHG_STATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
                $mkInvoice = (int)$this->scopeConfig->getValue(Data::XML_PATH_MK_INVOICE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

                if ($chgState == 1) {
                    if ($order->getState() != \Magento\Sales\Model\Order::STATE_PROCESSING) {
                        $sendOrderUpdateEmail = true;
                    }
                    $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_PROCESSING, __('The payment has been accepted.'), true);
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
                    $order->save();
                    if ($mkInvoice == 1) {
                        $this->makeInvoiceFromOrder($order);
                    } else {
                        $order->setTotalPaid($order->getGrandTotal());
                    }
                }
                $order->save();

                // zapis karty
                $requestedMethod = $this->_getRequest()->getPost('p24_method');
                if (in_array((int)$requestedMethod, Recurring::getChannelsCards())) {
                    $recurring = $this->objectManager->create('Dialcom\Przelewy\Model\Recurring');
                    $recurring->saveUsedCard($order->getData('customer_id'), (int)$this->_getRequest()->getPost('p24_order_id'), $storeId);
                }
            } else {
                if ($order->getState() != \Magento\Sales\Model\Order::STATE_HOLDED) {
                    $sendOrderUpdateEmail = true;
                }

                $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_HOLDED, __('Payment error.') . ' ' . $ret['errorMessage'], true);
                $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED, true);
            }

            if ($sendOrderUpdateEmail == true) {
                $order->setSendEmail(true);
            }
            $order->save();

            return $ret === true;
        }

        return false;
    }

    public function getGaOrderId($orderId)
    {
        $orderId = (int) $orderId;
        $order = $this->objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
        $storeId = $order->getStoreId();
        $payinshop = (int)$this->scopeConfig->getValue(Data::XML_PATH_PAYINSHOP, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $gaBeforePayment = (int)$this->scopeConfig->getValue(Data::XML_PATH_GA_BEFORE_PAYMENT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $paymentData = $order->getPayment()->getData();
        $additionalInformation = filter_var($paymentData['additional_information'], FILTER_SANITIZE_STRING);
        $payInShopByCard = $payinshop && in_array($additionalInformation['method_id'], Recurring::getChannelsCards());

        $gaOrderId = ($gaBeforePayment === 1 && !$payInShopByCard) ? 0 : $this->_request->getParam('ga_order_id', 0);

        return $gaOrderId;
    }
}

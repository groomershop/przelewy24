<?php

namespace Dialcom\Przelewy\Model\Config;

use Dialcom\Przelewy\Helper\Data;
use Dialcom\Przelewy\Model\AdvancedValidator;
use Dialcom\Przelewy\Przelewy24Class;

class Channels
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    private $storeManager;

    /**
     * Channels constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder
    )
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
    }

    public static function getChannelsInstallment()
    {
        return [72, 129, 136];
    }

    public static function getChannelsNonPln()
    {
        return [66, 92, 124, 140, 145, 152, 173, 218];
    }

    private static function getWsdlService($merchantId)
    {
        return str_replace('[P24_MERCHANT_ID]', $merchantId, 'external/[P24_MERCHANT_ID].wsdl');
    }

    private static function getWsdlCCService()
    {
        return 'external/wsdl/charge_card_service.php?wsdl';
    }

    private static function soap_method_exists($soapClient, $method)
    {
        $list = $soapClient->__getFunctions();

        if (is_array($list)) {
            foreach ($list as $line) {
                list($type, $name) = explode(' ', $line, 2);

                if (strpos($name, $method) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public function runIvrPayment($order)
    {
        $storeId = $order->getStoreId();
        $fullConfig = Waluty::getFullConfig($order->getOrderCurrencyCode(), $this->scopeConfig, $storeId);

        $P24C = new Przelewy24Class(
            $fullConfig['merchant_id'],
            $fullConfig['shop_id'],
            $fullConfig['salt'],
            $this->scopeConfig->getValue(Data::XML_PATH_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == '1'
        );

        try {
            $s = new \SoapClient($P24C->getHost() . self::getWsdlService($fullConfig['merchant_id']), array('trace' => true, 'exceptions' => true));
            if (self::soap_method_exists($s, 'TransactionMotoCallBackRegister')) {

                // usunięcie prefiksu kraju
                $clientPhone = $order->getShippingAddress()->getTelephone();
                if (strpos($clientPhone, '+48') === 0) $clientPhone = substr($clientPhone, 3);
                elseif (strpos($clientPhone, '0048') === 0) $clientPhone = substr($clientPhone, 4);
                elseif (strpos($clientPhone, '48') === 0) $clientPhone = substr($clientPhone, 2);
                $sessionId = substr((int) $order->getEntityId() . '|' . md5(uniqid(mt_rand(), true) . ':' . microtime(true)), 0, 100);
                $res = $s->__call('TransactionMotoCallBackRegister', array(
                    'login' => $fullConfig['merchant_id'],
                    'pass' => $fullConfig['salt'],
                    'details' => array(
                        'clientPhone' => filter_var($clientPhone, FILTER_SANITIZE_STRING),
                        'amount' => number_format($order->getGrandTotal() * 100, 0, "", ""),
                        'currency' => filter_var($order->getOrderCurrencyCode(), FILTER_SANITIZE_STRING),
                        'paymentId' => (int) $order->getEntityId(),
                        'description' => __('Order').' '. $order->getIncrementId(),
                        'sessionId' => $sessionId,
                        'clientEmail' => filter_var($order->getCustomerEmail(), FILTER_SANITIZE_EMAIL),
                        'merchantEmail' => filter_var($this->scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),FILTER_SANITIZE_EMAIL),
                        'client' => filter_var($order->getBillingAddress()->getData('firstname') . ' ' . $order->getBillingAddress()->getData('lastname'), FILTER_SANITIZE_STRING),
                        'urlStatus' => filter_var($this->urlBuilder->getDirectUrl('przelewy/przelewy/status'), FILTER_SANITIZE_URL),
                        'typeOfResponse' => 'post',
                        'sendEmail' => 0,
                        'time' => 0,
                        'additionalInfo' => '',
                    )
                ));
                $order->setData('p24_session_id', $sessionId);
                $order->save();
                if ($res->error->errorCode > 0) {
                    error_log(__METHOD__ . ' ' . $res->error->errorMessage);
                    throw new \Exception($res->error->errorMessage);
                }

                return __('IVR payment successful!');
            }
        } catch (\Exception $e) {
            error_log(__METHOD__ . ' ' . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
        throw new \Exception(__('IVR payment failed'));
    }

    public function toOptionArray($currency = 'PLN')
    {
        $scopeId = AdvancedValidator::getStoreIdFromUrl();
        $scopeName = AdvancedValidator::getScopeNameFromUrl();

        $order_id = $this->objectManager->get('Magento\Checkout\Model\Session')->getLastOrderId();

        if ($order_id != 0) {
            $order = $this->objectManager->create('Magento\Sales\Model\Order')->load($order_id);
            $scopeId = $order->getStoreId();
        }

        $fullConfig = Waluty::getFullConfig($currency, $this->scopeConfig, $scopeId, $scopeName);
        $payment_list = [];
        $P24C = new Przelewy24Class(
            $fullConfig['merchant_id'],
            $fullConfig['shop_id'],
            $fullConfig['salt'],
            $this->scopeConfig->getValue(Data::XML_PATH_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == '1'
        );

        try {
            $s = new \SoapClient($P24C->getHost() . $this->getWsdlService($this->scopeConfig->getValue('payment/dialcom_przelewy/merchant_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)), array('trace' => true, 'exceptions' => true));
            $res = $s->PaymentMethods(
                $fullConfig['shop_id'],
                $fullConfig['api'],
                substr($this->objectManager->get('Magento\Framework\Locale\Resolver')->getLocale(), 0, 2)
            );
        } catch (\Exception $e) {
            error_log(__METHOD__ . ' ' . $e->getMessage());
        }

        if (isset($res) && $res->error->errorCode === 0) {
            $thereIs218 = false;

            foreach ($res->result as $item) {
                if (218 === (int)$item->id) {
                    $thereIs218 = true;
                }

                $payment_list[] = ['value' => $item->id, 'label' => $item->name];
            }

            if ($thereIs218) {
                $payment_list = array_filter($payment_list, function ($payment) {
                    return !in_array($payment['value'], [142, 145]);
                });
            }
        }

        if ($this->getCurrentCurrency() !== 'PLN') {
            $payment_list = array_filter($payment_list, function ($payment) {
                return (int)$payment['value'] !== Data::P24NOW_METHOD_ID;
            });
        }

        return $payment_list;
    }

    private function getCurrentCurrency()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->create('\Magento\Store\Model\StoreManagerInterface');

        return $storeManager->getStore()->getCurrentCurrency()->getCode();
    }
}

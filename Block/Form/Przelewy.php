<?php

namespace Dialcom\Przelewy\Block\Form;

use Dialcom\Przelewy\Helper\Data;
use Dialcom\Przelewy\Model\Payment\Przelewy as Payment;
use Dialcom\Przelewy\Model\Recurring;
use Magento\Backend\Model\Session\Quote;

class Przelewy extends \Magento\Payment\Block\Form
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
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var Quote
     */
    protected $sessionQuote;

    /**
     * @var Payment
     */
    protected $payment;

    /**
     * Przelewy constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Quote $sessionQuote,
        Payment $przelewyPayment,
        array $data = []
    )
    {
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $context->getStoreManager();
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->customerSession = $this->objectManager->get('Magento\Customer\Model\Session');
        $this->urlBuilder = $context->getUrlBuilder();
        $this->sessionQuote = $sessionQuote;
        $this->payment = $przelewyPayment;
        parent::__construct($context, $data);
    }

//    protected function _construct()
//    {
//        parent::_construct();
//        $this->setTemplate('dialcom/przelewy/form.phtml');
//    }

    public function getCards()
    {
        if (!is_null($this->customerSession) && $this->customerSession->isLoggedIn()) {
            $customerData = $this->customerSession->getCustomer();
            return Recurring::getCards($customerData->getId());
        }
        return array();
    }

    public function getDescription()
    {
        return __($this->scopeConfig->getValue(Data::XML_PATH_TEXT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }

    public function getLastPaymentMethod()
    {
        try {
            if ($this->customerSession->isLoggedIn()) {
                $customerId = $this->customerSession->getCustomer()->getId();
                if (!is_null($customerId)) {
                    $collection = $this->objectManager->create('Magento\Sales\Model\ResourceModel\Order\CollectionFactory')
                        ->create()
                        ->AddFieldToFilter(
                            'customer_id',
                            array(
                                'eq' => $customerId
                            )
                        );
                    $collection->setOrder('created_at', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_DESC);
                    $order = $collection->getFirstItem();
                    if ($order && $order->getPayment()) {
                        $paymentData = $order->getPayment()->getData();
                        if (isset($paymentData['additional_information']['method_id'])) {
                            $lastMethod = $paymentData['additional_information']['method_id'];
                            if (!in_array($lastMethod, Recurring::getChannelsCards())) {
                                return $lastMethod;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            error_log(__METHOD__ . ' ' . $e->getMessage());
        }
        return false;
    }

    public function getPaymentChannels()
    {
        $channels = $this->objectManager->create('Dialcom\Przelewy\Model\Config\Channels');
        $currency = strtoupper($this->storeManager->getStore()->getCurrentCurrencyCode());
        $nonPln = $channels::getChannelsNonPln();
        $payment_list = array();
        foreach ($channels->toOptionArray() as $item) {
            if ($currency == 'PLN' || in_array($item['value'], $nonPln)) {
                $payment_list[$item['value']] = $item['label'];
            }
        }
        return $payment_list;
    }

    public function getBankHtml($bank_id, $bank_name, $text = '', $cc_id = '', $class = '')
    {
        $bank_id = $this->escapeHtml($bank_id);
        $bank_name = $this->escapeHtml($bank_name);
        $text = $this->escapeHtml($text);
        $cc_id = $this->escapeHtml($cc_id);
        $class = $this->escapeHtml($class);
        return '<a class="bank-box ' . $class . '" data-id="' . $bank_id . '" data-cc="' . $cc_id . '">' .
        '<div class="bank-logo bank-logo-' . $bank_id . '">' .
        (empty($text) ? "" : "<span>{$text}</span>") .
        '</div><div class="bank-name">' . $bank_name . '</div></a>';
    }

    /**
     * Get bank text.
     *
     * @param int $bankId
     * @param string $bankName
     * @param bool $isChecked
     * @param string $ccId
     * @param string $text
     *
     * @return string
     */
    public function getBankTxt($bankId, $bankName, $isChecked = false, $ccId = '', $text = '')
    {
        return $this->getBankTxtUnescaped($bankId, $this->escapeHtml($bankName), $isChecked, $ccId, $text);
    }

    /**
     * Get bank text unescaped.
     *
     * @param int $bankId
     * @param string $bankName
     * @param bool $isChecked
     * @param string $ccId
     * @param string $text
     *
     * @return string
     */
    public function getBankTxtUnescaped($bankId, $bankName, $isChecked = false, $ccId = '', $text = '')
    {
        $bankId = $this->escapeHtml($bankId);
        $text = $this->escapeHtml($text);
        $ccId = $this->escapeHtml($ccId);

        return
            '<li><div class="input-box  bank-item">' .
            '<input
            id="przelewy_method_id_' . $bankId . '-' . $ccId . '"
            name="payment_method_id"
            data-id="' . $bankId . '"
            data-cc="' . $ccId . '"
            data-text="' . $text . '" ' .
            ' class="radio" type="radio" ' . ($isChecked ? 'checked="checked"' : '') . ' />' .
            '<label for="przelewy_method_id_' . $bankId . '-' . $ccId . '">' . $bankName . '</label>' .
            '</div></li>';
    }

    public function p24getCssUrl()
    {
        return $this->getAssetUrl('Dialcom_Przelewy::css/css_paymethods.css');
    }

    public function p24getJsUrl()
    {
        return $this->getAssetUrl('Dialcom_Przelewy::js/payment.js');
    }

    public function getCardImgUrl()
    {
        return $this->getAssetUrl('Dialcom_Przelewy::images/cc_empty.png');
    }

    private function getAssetUrl($asset)
    {
        $assetRepository = $this->objectManager->get('Magento\Framework\View\Asset\Repository');
        return $assetRepository->createAsset($asset)->getUrl();
    }

    public function getMyCardsUrl($cardId)
    {
        return $this->urlBuilder->getUrl('przelewy/przelewy/mycards', array('cardrm' => $cardId));
    }

    public function getLogoUrl()
    {
        return $this->getAssetUrl('Dialcom_Przelewy::images/logo_small.png');
    }

    /**
     * Get extra charge amount
     *
     * @return float|null
     */
    public function getExtraChargeAmount()
    {
        $amount = number_format($this->sessionQuote->getQuote()->getGrandTotal() * 100, 0, '', '');

        $extraChargeAmount = $this->payment->getExtrachargeAmountByAmount($amount);

        if (0 === $extraChargeAmount) {
            return null;
        }

        return $this->objectManager->create('Magento\Framework\Pricing\Helper\Data')->currency(
            round($extraChargeAmount / 100, 2),
            true,
            false
        );
    }
}

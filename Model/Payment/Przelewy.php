<?php

namespace Dialcom\Przelewy\Model\Payment;

use Dialcom\Przelewy\Helper\Data;
use Dialcom\Przelewy\Model\Recurring;
use Dialcom\Przelewy\Przelewy24Class;
use Dialcom\Przelewy\Services\PayloadForRestTransaction;
use Magento\Sales\Model\Order;

class Przelewy extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_PRZELEWY_CODE = 'dialcom_przelewy';
    protected $_code = self::PAYMENT_METHOD_PRZELEWY_CODE;
    protected $_formBlockType = 'Dialcom\Przelewy\Block\Form\Przelewy';
    protected $_infoBlockType = 'Dialcom\Przelewy\Block\Info\Przelewy';

    protected $_isGateway = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;

    private $P24 = null;
    private $storeId = 0;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $_storeManager;

    /**
     * Przelewy constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /*        $this->request = $this->objectManager->get('Magento\Framework\App\RequestInterface'); // Magento\Framework\App\Request\Http
                $this->urlBuilder = $this->objectManager->get('\Magento\Framework\UrlInterface');*/
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->customerSession = $this->objectManager->get('Magento\Customer\Model\Session');
        $this->_storeManager = $storeManager;
        $this->storeId = $this->_storeManager->getStore()->getStoreId();

        $this->P24 = new Przelewy24Class($this->getMerchantId(),
            $this->getShopId(),
            $this->getSalt(),
            ($this->getTestMode() == '1'));

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public static function requestGet($url)
    {
        $isCurl = function_exists('curl_init') && function_exists('curl_setopt') && function_exists('curl_exec') && function_exists('curl_close');

        if ($isCurl) {
            $userAgent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
            $curlConnection = curl_init();
            curl_setopt($curlConnection, CURLOPT_URL, $url);
            curl_setopt($curlConnection, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curlConnection, CURLOPT_USERAGENT, $userAgent);
            curl_setopt($curlConnection, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlConnection, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($curlConnection);
            curl_close($curlConnection);
            return $result;
        }
        return "";
    }

    public static function getMinRatyAmount()
    {
        return 300;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        if(!empty($data->_data['additional_data'])){
            $additionalData = $data->_data['additional_data'];

            $info = $this->getInfoInstance();
            if (isset($additionalData['method_id'])) {
                $info->setAdditionalInformation('method_id', $additionalData['method_id']);
            }
            if (isset($additionalData['method_name'])) {
                $info->setAdditionalInformation('method_name', $additionalData['method_name']);
            }
            if (isset($additionalData['accept_regulations'])) {
                $info->setAdditionalInformation('accept_regulations', $additionalData['accept_regulations']);
            }
            if (isset($additionalData['cc_id'])) {
                $info->setAdditionalInformation('cc_id', $additionalData['cc_id']);
            }
            if (isset($additionalData['cc_name'])) {
                $info->setAdditionalInformation('cc_name', $additionalData['cc_name']);
            }
            if (isset($additionalData['p24_forget'])) {
                $info->setAdditionalInformation('p24_forget', $additionalData['p24_forget']);
                Recurring::setP24Forget((int)$additionalData['p24_forget'] === 1);
            }
        }
        return $this;
    }

    public function getText()
    {
        return $this->getConfigData("text");
    }

    public function getOrderPlaceRedirectUrl()
    {
        return $this->urlBuilder->getUrl('przelewy/przelewy/redirect', ['noCache' => time() . uniqid(true)]);
    }

    public function getCheckout()
    {
        return $this->objectManager->get('Magento\Checkout\Model\Session');
    }

    /**
     * To remember:
     *
     * session:
     *  getLastOrderId for internal use, int
     *  getLastRealOrderId for external, string can contain prefixes
     * order:
     *  getEntityId - int id
     *  getIncrementId - string can contain prefixes
     * load by:
     *  load - loads by entityId
     *  loadByIncrementId - loads by calculatedId
     * We should name
     *  $entityOrderId for id internal.
     *  $calculatedOrderId for outside-of-the-box purposes.
     *
     * Therefore
     *  outside use of entity id is bad practice for security reasons.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getRedirectionFormData($calculatedOrderId = null)
    {
        if (is_null($calculatedOrderId)) {
            $calculatedOrderId = $this->getCheckout()->getLastOrderId();
        }

        $order = $this->objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($calculatedOrderId);
        if(empty($order->getData())){
            $order = $this->objectManager->create('Magento\Sales\Model\Order')->load($calculatedOrderId);
        }
        $this->storeId = $order->getStoreId();
        $sessionId = $order->getData('p24_session_id');
        if (!$sessionId) {
            $sessionId = Data::getSessionId($order->getEntityId());
            $order->setData('p24_session_id', $sessionId);
        }
        $order->save();
        $amount = number_format($order->getGrandTotal() * 100, 0, "", "");
        $currency = $order->getOrderCurrencyCode();

        $data = [
            'p24_session_id' => $sessionId,
            'p24_merchant_id' => (int) $this->getMerchantId(),
            'p24_pos_id' => (int) $this->getShopId(),
            'p24_email' => filter_var($order->getCustomerEmail(), FILTER_SANITIZE_EMAIL),
            'p24_amount' => $amount,
            'p24_currency' => $currency,
            'p24_description' => filter_var(__('Order').' '. $calculatedOrderId, FILTER_SANITIZE_STRING),
            'p24_language' => strtolower(substr($this->objectManager->get('Magento\Framework\Locale\Resolver')->getLocale(), 0, 2)),
            'p24_client' => filter_var($order->getBillingAddress()->getData('firstname') . ' ' . $order->getBillingAddress()->getData('lastname'), FILTER_SANITIZE_STRING),
            'p24_address' => filter_var($order->getBillingAddress()->getData('street'), FILTER_SANITIZE_STRING),
            'p24_city' => filter_var($order->getBillingAddress()->getData('city'), FILTER_SANITIZE_STRING),
            'p24_zip' => $order->getBillingAddress()->getData('postcode'),
            'p24_country' => 'PL',
            'p24_encoding' => 'utf-8',
            'p24_url_status' => filter_var($this->urlBuilder->getUrl('przelewy/przelewy/status'),FILTER_SANITIZE_URL),
            'p24_url_return' => filter_var($this->urlBuilder->getUrl('przelewy/przelewy/returnUrl', ['ga_order_id' => $calculatedOrderId]),FILTER_SANITIZE_URL),
            'p24_api_version' => filter_var(P24_VERSION, FILTER_SANITIZE_URL),
            'p24_ecommerce' => 'magento2_' . $this->objectManager->get('Magento\Framework\App\ProductMetadata')->getVersion(),
            'p24_ecommerce2' => $this->objectManager->get('Magento\Framework\Module\ModuleList')->getOne('Dialcom_Przelewy')['setup_version'],
            'p24_wait_for_result' => $this->getWaitForResult() ? '1' : '0',
            'p24_shipping' => number_format($order->getShippingAmount() * 100, 0, "", ""),
        ];

        $productsInfo = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $productId = $item->getProductId();
            $product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($productId);

            $productsInfo[] = [
                'name' => filter_var($product->getName(), FILTER_SANITIZE_STRING),
                'description' => $product->getDescription(),
                'quantity' => (int)$item->getQtyOrdered(),
                'price' => (int)number_format($item->getPrice() * 100, 0, "", ""),
                'number' => $productId,
            ];
        }

        $translations = [
            'virtual_product_name' => __('Extra charge [VAT and discounts]')->__toString(),
            'cart_as_product' => __('Your order')->__toString(),
        ];

        $p24Product = new \Przelewy24Product($translations);
        $p24ProductItems = $p24Product->prepareCartItems($amount, $productsInfo, $data['p24_shipping']);

        $data = array_merge($data, $p24ProductItems);

        $data['p24_sign'] = $this->P24->trnDirectSign($data);

        $info = $order->getPayment()->getMethodInstance()->getInfoInstance();
        if ((int)$info->getAdditionalInformation('method_id') > 0) {
            $data['p24_method'] = (int)$info->getAdditionalInformation('method_id');
        }

        $data['p24_time_limit'] = (null === $this->getTimeLimit()) ? 0 : $this->getTimeLimit();

        if ($this->getPaySlow()) {
            $data['p24_channel'] = 16;
        }

        if ((int)$info->getAdditionalInformation('accept_regulations') > 0) {
            $data['p24_regulation_accept'] = 1;
        }

        $this->P24->checkMandatoryFieldsForAction($data, 'trnDirect');
        return (array)@$data;
    }

    /**
     * Get transaction data.
     *
     * @param int    $calculatedOrderId
     * @param string $methodRefId
     *
     * @return PayloadForRestTransaction
     * @throws \Exception
     */
    public function getTransactionData($calculatedOrderId, $methodRefId)
    {
        $data = $this->getRedirectionFormData($calculatedOrderId);
        $statusUrl = $this->urlBuilder->getUrl('przelewy/przelewy/status', ['response' => 'rest']);
        $payload = new PayloadForRestTransaction();
        $payload->merchantId = (int)$data['p24_merchant_id'];
        $payload->posId = (int)$data['p24_pos_id'];
        $payload->sessionId = (string)$data['p24_session_id'];
        $payload->amount = (int)$data['p24_amount'];
        $payload->currency = (string)$data['p24_currency'];
        $payload->description = (string)$data['p24_description'];
        $payload->email = (string)$data['p24_email'];
        $payload->client = (string)$data['p24_client'];
        $payload->address = (string)$data['p24_address'];
        $payload->zip = (string)$data['p24_zip'];
        $payload->city = (string)$data['p24_city'];
        $payload->country = (string)$data['p24_country'];
        $payload->language = (string)$data['p24_language'];
        $payload->urlReturn = (string)$data['p24_url_return'];
        $payload->urlStatus = filter_var($statusUrl, FILTER_SANITIZE_URL);
        $payload->regulationAccept = (bool)$data['p24_regulation_accept'];
        $payload->shipping = (string)$data['p24_shipping'];
        $payload->encoding = (string)$data['p24_encoding'];
        $payload->methodRefId = (string)$methodRefId;

        return $payload;
    }

    public function getMerchantId()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_MERCHANT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getShopId()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_SHOP_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getSalt()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_SALT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getTestMode()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getTimeLimit()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_TIMELIMIT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getWaitForResult()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_WAIT_FOR_RESULT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getPaySlow()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_PAYSLOW, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getPayMethodFirst()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_PAYMETHOD_FIRST, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getPayMethodSecond()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_PAYMETHOD_SECOND, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getRegulationAccept()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_P24REGULATIONS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getOneClick()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_ONECLICK, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getShowPayMethods()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_SHOWPAYMENTMETHODS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getPromoteP24NOWPayment()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_PROMOTE_P24NOW_PAYMENT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function isEnabledPromoteP24NOWPayment()
    {
        return $this->getPromoteP24NOWPayment() === '1';
    }

    public function getPromotedP24NOWTileWidth()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_PROMOTED_P24NOW_TILE_WIDTH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getPromoteP24NOWInPaymentMethods()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_PROMOTE_P24NOW_IN_PAYMENT_METHODS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function isEnabledPromoteP24NOWInPaymentMethods()
    {
        return $this->getPromoteP24NOWInPaymentMethods() === '1';
    }

    public function getUseGraphical()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_USEGRAPHICAL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getInstallment()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_INSTALLMENT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getShowPromoted()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_SHOW_PROMOTED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getPayMethodPromoted()
    {
        return $this->scopeConfig->getValue(Data::XML_PATH_PAYMETHOD_PROMOTED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getTotalPrice()
    {
        return number_format($this->getCheckout()->getQuote()->getBaseGrandTotal(), 2, '.', '');
    }

    public function getPaymentURI()
    {
        return $this->P24->trnDirectUrl();
    }

    public function getCountriesToOptionArray()
    {
        $new = [];
        foreach ($this->_sa_countries as $key => $option) {
            $new[] = [
                'value' => $key,
                'label' => $option
            ];
        }

        return $new;
    }

    private $_sa_countries = [
        'AL' => 'Albania',
        'AUS' => 'Australia',
        'A' => 'Austria',
        'BY' => 'Belarus',
        'B' => 'Belgium',
        'BIH' => 'Bosnia and Herzegowina',
        'BR' => 'Brazil',
        'BG' => 'Bulgaria',
        'CDN' => 'Canada',
        'HR' => 'Croatia',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'ET' => 'Egypt',
        'EST' => 'Estonia',
        'FIN' => 'Finland',
        'F' => 'France',
        'DE' => 'Germany',
        'GR' => 'Greece',
        'H' => 'Hungary',
        'IS' => 'Iceland',
        'IND' => 'India',
        'IRL' => 'Ireland',
        'I' => 'Italy',
        'J' => 'Japan',
        'LV' => 'Latvia',
        'FL' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'L' => 'Luxembourg',
        'NL' => 'Netherlands',
        'N' => 'Norway',
        'PL' => 'Polska',
        'P' => 'Portugal',
        'RO' => 'Romania',
        'RUS' => 'Russian Federation',
        'SK' => 'Slovakia (Slovak Republic)',
        'SLO' => 'Slovenia',
        'E' => 'Spain',
        'S' => 'Sweden',
        'CH' => 'Switzerland',
        'TR' => 'Turkey',
        'UA' => 'Ukraine',
        'UK' => 'United Kingdom',
        'USA' => 'United States',
    ];

    /**
     * Zwraca kwotę dodatkowej opłaty przy wyborze przelewy24 na podstawie order_id.
     *
     * @param string|int $orderId Internal order id (entityId)
     *
     * @return int|mixed
     */
    public function getExtrachargeAmount($orderId)
    {
        $order = $this->objectManager->create('Magento\Sales\Model\Order')->load($orderId);
        $amount = number_format($order->getGrandTotal() * 100, 0, "", "");

        return self::getExtrachargeAmountByAmount($amount);
    }

    /*
     * Zwraca kwotę dodatkowej opłaty przy wyborze przelewy24 na podstawie kwoty
     * @param int
     * @return float
     *
     * */
    public function getExtrachargeAmountByAmount($amount)
    {
        $amount = round($amount);
        $extracharge_amount = 0;

        if (
            $this->scopeConfig->getValue(Data::XML_PATH_EXTRACHARGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,  $this->storeId) == 1 &&
            $amount > 0 &&
            ((float)$this->scopeConfig->getValue(Data::XML_PATH_EXTRACHARGE_PRODUCT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,  $this->storeId) > 0 ||
                (float)$this->scopeConfig->getValue(Data::XML_PATH_EXTRACHARGE_PERCENT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,  $this->storeId) > 0 )
        ) {

            $inc_amount_settings = (float)$this->scopeConfig->getValue(Data::XML_PATH_EXTRACHARGE_AMOUNT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,  $this->storeId);
            $inc_percent_settings = (float)$this->scopeConfig->getValue(Data::XML_PATH_EXTRACHARGE_PERCENT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,  $this->storeId);

            $inc_amount = round($inc_amount_settings > 0 ? $inc_amount_settings * 100 : 0);
            $inc_percent = round($inc_percent_settings > 0 ? $inc_percent_settings / 100 * $amount : 0);

            $extracharge_amount = max($inc_amount, $inc_percent);
        }

        return $extracharge_amount;
    }

    /**
     * @param string|int $order_id
     */
    public function addExtracharge($order_id)
    {
        $extracharge_amount = self::getExtrachargeAmount($order_id);
        $extracharge_product = (int)$this->scopeConfig->getValue(Data::XML_PATH_EXTRACHARGE_PRODUCT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($this->scopeConfig->getValue(Data::XML_PATH_EXTRACHARGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 1 && $extracharge_amount > 0 && $extracharge_product > 0) {

            $order = $this->objectManager->create('Magento\Sales\Model\Order')->load($order_id);
            $product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($extracharge_product);

            $foundExtracharge = false;
            foreach ($order->getAllItems() as $item) {
                if ($item->getSku() == $product->getSku()) $foundExtracharge = true;
            }

            if (!$foundExtracharge) {
                try {
                    $rowTotal = $extracharge_amount / 100;

                    $qty = 1;
                    $orderItem = $this->objectManager->create('Magento\Sales\Model\Order\Item')
                        ->setStoreId($order->getStore()->getStoreId())
                        ->setQuoteItemId(NULL)
                        ->setQuoteParentItemId(NULL)
                        ->setProductId($product->getId())
                        ->setProductType($product->getTypeId())
                        ->setQtyBackordered(NULL)
                        ->setTotalQtyOrdered($qty)
                        ->setQtyOrdered($qty)
                        ->setName($product->getName())
                        ->setSku($product->getSku())
                        ->setPrice($rowTotal)
                        ->setBasePrice($rowTotal)
                        ->setOriginalPrice($rowTotal)
                        ->setRowTotal($rowTotal)
                        ->setBaseRowTotal($rowTotal)
                        ->setOrder($order);
                    $orderItem->save();

                    //  $quote = $this->objectManager->create('Magento\Quote\Model\QuoteRepository')->addFieldToFilter("entity_id", $order->getQuoteId())->getFirstItem();

                    $order->setSubtotal($rowTotal + $order->getSubtotal())
                        ->setBaseSubtotal($rowTotal + $order->getBaseSubtotal())
                        ->setGrandTotal($rowTotal + $order->getGrandTotal())
                        ->setBaseGrandTotal($rowTotal + $order->getBaseGrandTotal());


                    // $quote->save();
                    $order->save();
                } catch (\Exception $e) {
                    $this->logger->debug([__METHOD__ . ' ' . $e->getMessage()]);
                }
            }
        }
    }

    public function getBlock()
    {
        return $this->objectManager->create('Dialcom\Przelewy\Block\Form\Przelewy');
    }

    /**
     * @param $order
     * @param $product
     * @param $discount
     */
    private function updateOrder($order, $product, $discount)
    {
        $qty = 1;
        $orderItem = $this->objectManager->create('Magento\Sales\Model\Order\Item')
            ->setStoreId($order->getStore()->getStoreId())
            ->setQuoteItemId(NULL)
            ->setQuoteParentItemId(NULL)
            ->setProductId($product->getId())
            ->setProductType($product->getTypeId())
            ->setQtyBackordered(NULL)
            ->setTotalQtyOrdered($qty)
            ->setQtyOrdered($qty)
            ->setName($product->getName())
            ->setSku($product->getSku())
            ->setPrice(-$discount)
            ->setBasePrice(-$discount)
            ->setOriginalPrice(-$discount)
            ->setRowTotal(-$discount)
            ->setBaseRowTotal(-$discount)
            ->setOrder($order);
        $orderItem->save();
    }

    /**
     * Add extra charge for order.
     *
     * @param Order $order
     */
    public function addExtraChargeToOrder($order)
    {
        $extraChargeEnabled = (int)$this->scopeConfig->getValue(Data::XML_PATH_EXTRACHARGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE) === 1;
        $extraChargeProduct = (int)$this->scopeConfig->getValue(Data::XML_PATH_EXTRACHARGE_PRODUCT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $extraChargeAmount = $this->getExtraChargeAmountByOrder($order);

        if (!$extraChargeEnabled || $extraChargeProduct <= 0 || $extraChargeAmount <= 0) {
            return;
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($extraChargeProduct);

        $rowTotal = round($extraChargeAmount / 100, 2);

        $extraChargeAlreadyInCart = false;
        // if "Extra Charge" virtual product is already in the cart,
        // we update it's price and qty
        foreach ($order->getAllItems() as $item) {
            if ($item->getSku() === $product->getSku()) {
                $extraChargeAlreadyInCart = true;

                $item->setPrice($rowTotal)
                    ->setBasePrice($rowTotal)
                    ->setOriginalPrice($rowTotal)
                    ->setRowTotal($rowTotal)
                    ->setBaseRowTotal($rowTotal)
                    ->setQtyOrdered(1);
            }
        }

        try {
            if (!$extraChargeAlreadyInCart) {
                $orderItem = $this->objectManager->create('Magento\Sales\Model\Order\Item')
                    ->setStoreId($order->getStore()->getStoreId())
                    ->setQuoteItemId(null)
                    ->setQuoteParentItemId(null)
                    ->setProductId($product->getId())
                    ->setProductType($product->getTypeId())
                    ->setQtyBackordered(null)
                    ->setTotalQtyOrdered(1)
                    ->setQtyOrdered(1)
                    ->setName($product->getName())
                    ->setSku($product->getSku())
                    ->setPrice($rowTotal)
                    ->setBasePrice($rowTotal)
                    ->setOriginalPrice($rowTotal)
                    ->setRowTotal($rowTotal)
                    ->setBaseRowTotal($rowTotal)
                    ->setOrder($order);

                $order->addItem($orderItem);
            }

            $order->setSubtotal($rowTotal + $order->getSubtotal())
                ->setBaseSubtotal($rowTotal + $order->getBaseSubtotal())
                ->setGrandTotal($rowTotal + $order->getGrandTotal())
                ->setBaseGrandTotal($rowTotal + $order->getBaseGrandTotal());
        } catch (\Exception $e) {
            $this->logger->debug([__METHOD__ . ' ' . $e->getMessage()]);
        }
    }

    /**
     * Calculate extra charge amount by order.
     *
     * @param Order $order
     *
     * @return int
     */
    public function getExtraChargeAmountByOrder($order)
    {
        $amount = (int) number_format($order->getGrandTotal() * 100, 0, '', '');

        return $this->getExtrachargeAmountByAmount($amount);
    }
}

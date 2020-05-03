<?php
namespace Dialcom\Przelewy\Model;

use Dialcom\Przelewy\Helper\Data;
use Dialcom\Przelewy\Przelewy24Class;

class Recurring extends \Magento\Framework\Model\AbstractModel
{
    private $objectManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ResourceModel\Recurring\CollectionFactory
     */
    protected $recurringCollection;

    /**
     * Recurring constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ResourceModel\Recurring\CollectionFactory $recurringCollection
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Dialcom\Przelewy\Model\ResourceModel\Recurring\CollectionFactory $recurringCollection,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->recurringCollection = $recurringCollection;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function _construct()
    {
        $this->_init('Dialcom\Przelewy\Model\ResourceModel\Recurring');
    }

    public static function getChannelsCards()
    {
        return array(140, 142, 145, 218);
        //return array(1,41,100,123,124,130,132,139,140,142,145,147,152);
    }

    public static function getWsdlCCService()
    {
        return 'external/wsdl/charge_card_service.php?wsdl';
    }

    static public function getCards($customer_id = null)
    {
        $result = array();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if (is_null($customer_id)) {
            $customerSession = $objectManager->get('Magento\Customer\Model\Session');
            $customer_id = (int) $customerSession->getCustomer()->getId();
        }

        $collection = $objectManager->create('Dialcom\Przelewy\Model\ResourceModel\Recurring\CollectionFactory')
            ->create()
            ->AddFieldToFilter(
                'customer',
                array(
                    'eq' => $customer_id
                )
            );

        if ($collection instanceof \Dialcom\Przelewy\Model\ResourceModel\Recurring\Collection) {
            $resultToArray = $collection->toArray();
            $result = isset($resultToArray['items']) ? $resultToArray['items'] : $resultToArray;
        }

        return $result;
    }

    static public function unregisterCard($card_id, $customer_id = null)
    {
        try {
            $card_id = (int) $card_id;
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            if (is_null($customer_id)) {
                $customerSession = $objectManager->get('Magento\Customer\Model\Session');
                $customer_id = (int) $customerSession->getCustomer()->getId();
            } else {
                $customer_id = (int)$customer_id;
            }
            $collection = self::getCards($customer_id);
            foreach ($collection as $card) {
                if ($card['id'] == $card_id) {
                    $objectManager->get('Dialcom\Przelewy\Model\Recurring')->load($card_id)->delete();
                }
                return;
            }

        } catch (\Exception $e) {
        }
    }

    private function _removeExpiredCards($customer_id)
    {
        $customer_id = (int) $customer_id;
        $collection = $this->getCards($customer_id);
        foreach ($collection as $card) {
            if (date("ym") > $card['expires']) {
                $this->unregisterCard($card['id'], $customer_id);
            }
        }
    }

    private function registerCard($customer_id, $ref_id, $expires, $mask, $type)
    {
        $customer_id = (int) $customer_id;
        if (!empty($ref_id) && date('ym') <= $expires) {
            try {
                $this->_removeExpiredCards($customer_id);
                $this->objectManager->create('Dialcom\Przelewy\Model\Recurring')
                    ->setData(array(
                        'customer' => $customer_id,
                        'reference' => $ref_id,
                        'expires' => $expires,
                        'mask' => $mask,
                        'card_type' => $type,
                    ))
                    ->save();
                $this->_removeExpiredCards($customer_id);
                return true;
            } catch (\Exception $e) {
                error_log(__METHOD__ . ' ' . $e->getMessage());
            }
        }
        return false;
    }

    public function saveUsedCard($customer_id, $order_id, $storeId = 0)
    {
        $customer_id = (int)$customer_id;
        $order_id = (int)$order_id;
        $storeId = (int)$storeId;

        $oneclickEnabled = $this->scopeConfig->getValue(Data::XML_PATH_ONECLICK, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId) == '1';
        $api_key = $this->scopeConfig->getValue(Data::XML_PATH_API_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

        $merchant_id = (int) $this->scopeConfig->getValue(Data::XML_PATH_MERCHANT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

        if ($customer_id > 0 && $oneclickEnabled && strlen($api_key) == 32) {

            // nie zapamiętuj karty - Customer sobie nie życzy
            if (self::getP24Forget($customer_id) == 1) return;

            try {
                $P24C = new Przelewy24Class(
                    $merchant_id,
                    $this->scopeConfig->getValue(Data::XML_PATH_SHOP_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId),
                    $this->scopeConfig->getValue(Data::XML_PATH_SALT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId),
                    ($this->scopeConfig->getValue(Data::XML_PATH_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId) == '1')
                );

                $soap = new \SoapClient($P24C->getHost() . $this->getWsdlCCService(), array('trace' => true, 'exceptions' => true));
                $res = $soap->GetTransactionReference($merchant_id, $api_key, $order_id);

                if ($res->error->errorCode === 0) {
                    $ref = $res->result->refId;
                    $exp = substr($res->result->cardExp, 2, 2) . substr($res->result->cardExp, 0, 2);
                    $this->registerCard($customer_id, $ref, $exp, $res->result->mask, $res->result->cardType);
                }
            } catch (\Exception $e) {
                error_log(__METHOD__ . ' ' . $e->getMessage());
            }
        }
    }

    private function refIdForCardId($card_id)
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            return $objectManager->create('Dialcom\Przelewy\Model\Recurring')->load((int)$card_id)->getReference();
        } catch (\Exception $e) {
        }
        return false;
    }

    public function chargeCard($order_id, $card_id)
    {
        $customerSession = $this->objectManager->get('Magento\Customer\Model\Session');
        $order_id = (int) $order_id;
        if ($customerSession->isLoggedIn()) {
            $order = $this->objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($order_id);
            $storeId = $order->getStoreId();

            $customer = $customerSession->getCustomer();
            $session_id = $order->getData('p24_session_id');
            if ($session_id === null) {
                $session_id = Data::getSessionId($order_id);
            }
            $amount = number_format($order->getGrandTotal() * 100, 0, "", "");
            $currency = $order->getOrderCurrencyCode();
            $merchant_id = (int) $this->scopeConfig->getValue(Data::XML_PATH_MERCHANT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
            $api_key = $this->scopeConfig->getValue(Data::XML_PATH_API_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
            $ref = $this->refIdForCardId((int)$card_id);

            if ($ref == false) return false;
            $P24C = new Przelewy24Class($merchant_id,
                $this->scopeConfig->getValue(Data::XML_PATH_SHOP_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId),
                $this->scopeConfig->getValue(Data::XML_PATH_SALT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId),
                ($this->scopeConfig->getValue(Data::XML_PATH_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId) == '1'));
            try {
                $s = new \SoapClient($P24C->getHost() . $this->getWsdlCCService(), array('trace' => true, 'exceptions' => true));
                $res = $s->ChargeCard(
                    $merchant_id, $api_key, $ref, $amount, $currency,
                    $customer->getEmail(), $session_id, $customer->getName(), __('odrder') . $order_id
                );
                $order->setData('p24_session_id', $session_id);
                $order->save();
                return $res->error->errorCode === 0;
            } catch (\Exception $e) {
                error_log(__METHOD__ . ' ' . $e->getMessage());
            }
        }
        return false;
    }

    /**
     * Function  get customer settings "to save my card"
     * @param null $customer_id ( is null to load Id in session)
     * @return int ( 0 or 1)
     */
    public static function getP24Forget($customer_id = null)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if (is_null($customer_id)) {
            $customerSession = $objectManager->get('Magento\Customer\Model\Session');
            $customer = $customerSession->getCustomer();
            $customer_id = $customer->getId();
        }

        $customer = $objectManager->create('Magento\Customer\Model\Customer')->load((int)$customer_id);

        return (int)$customer->getData('p24_forget');
    }

    private static function isAdmin()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $appState = $objectManager->get('Magento\Framework\App\State');
        return $appState->getAreaCode() === \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
    }

    /**
     * Function change user settings to save my card, 1 = not save, 0 = save
     * @param int $value
     */
    public static function setP24Forget($value)
    {
        if (!self::isAdmin()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerSession = $objectManager->get('Magento\Customer\Model\Session');
            $customer = $customerSession->getCustomer();

            $customer->setData('p24_forget', $value ? 1 : 0);
            $customer->save();

            $customers = $objectManager->create('Magento\Customer\Model\Customer')->load($customer->getId());
            $customers->setData('p24_forget', (int)$value);
            $customers->save();

            if ($value) {
                $collection = self::getCards($customer->getId());
                foreach ($collection as $card) {
                    self::unregisterCard($card['id'], $customer->getId());
                }
            }
        }
    }
}

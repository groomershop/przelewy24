<?php

namespace Dialcom\Przelewy\Model;

use Dialcom\Przelewy\Helper\Data;
use Dialcom\Przelewy\Przelewy24Class;
use Magento\Framework\Phrase;

/**
 * Description of Validator
 *
 * @author adamm
 */
class  AdvancedValidator extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    /**
     * @var mixed
     */
    protected $messageManager;

    /**
     * @var storeId
     */
    protected $storeId;

    /**I
     * AdvancedValidator constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->messageManager = $this->objectManager->get('Magento\Framework\Message\ManagerInterface');
        $this->storeId = (int)$this->getStoreId();
        $this->scope = $this->getScopeName();
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Get storeID from Url
     *
     * @return int
     */
    public static function getStoreIdFromUrl()
    {
        $scope_id = 0;
        $a_url = explode('/', $_SERVER['REQUEST_URI']);
        $a_url = array_reverse($a_url);
        $id = 0;
        if ((int)array_search('website', $a_url) > 0)
        {
            $id = (int)array_search('website', $a_url) ;
        }
        elseif ((int)array_search('store', $a_url) > 0)
        {
            $id = (int)array_search('store', $a_url) ;
        }
        if ($id > 0)
        {
            $scope_id = (int)$a_url[$id-1];
        }

        return $scope_id;
    }

    /**
     * @return int
     */
    public function getStoreId() {
        return self::getStoreIdFromUrl();

    }

    public static function getScopeNameFromUrl()
    {
        $name = 'SCOPE_STORE';
        $a_url = explode('/', $_SERVER['REQUEST_URI']);
        $a_url = array_reverse($a_url);
        if ((int)array_search('website', $a_url) > 0)
        {
            $name = 'SCOPE_WEBSITE';
        }
        elseif ((int)array_search('store', $a_url) > 0)
        {
            $name = 'SCOPE_STORE';
        }

        return $name;
    }

    /**
     * @return string
     */
    public function getScopeName()
    {
        return self::getScopeNameFromUrl();
    }
    public function beforeSave()
    {
        $path = $this->getPath();
        $field = substr($path, strrpos($path, '/') + 1);

        if ($field == 'oneclick') {
            $this->validateOncklick();
        } elseif ($field == 'api_key') {
            $this->validateApiKey();
        } elseif ($field == 'ga_key') {
            $this->validateGaKey();
        } elseif ($field == 'timelimit') {
            $this->validateTimeLimit();
        } elseif ($field == 'extracharge_amount') {
            $this->validateExtraChargeAmount();
        } elseif ($field == 'extracharge_percent') {
            $this->validateExtraChargePercent();
        }

        parent::beforeSave();
    }

    private function validateApiKey()
    {
        $ret = false;
        $value = $this->getValue();
        if (!empty($value)) {
            if (strlen($value) != 32 || !ctype_xdigit($value)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    new Phrase(
                        __('Przelewy24: The API key should have 32 characters')
                    )
                );
            } else {
                if (!extension_loaded('soap')) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        new Phrase(__('No soap extension'))
                    );
                } else {
                    $P24C = new Przelewy24Class(
                        $this->getMerchantId(),
                        $this->getShopId(),
                        $this->getSalt(),
                        $this->getMode()
                    );

                    try {
                        $s = new \SoapClient($P24C->getHost() . 'external/wsdl/service.php?wsdl', array('trace' => true, 'exceptions' => true));
                        $ret = $s->TestAccess($this->getShopId(), $value);
                    } catch (\Exception $e) {
                        error_log(__METHOD__ . ' ' . $e->getMessage());
                    }

                    if (!$ret) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            new Phrase(
                                __('Przelewy24: Incorrect API key')
                            )
                        );
                    }
                }
            }
        }
    }


    private function validateOncklick()
    {
        if (!!$this->getValue()) {
            $msg = __('Oneclick payments also require the account configuration at Przelewy24.pl, for this purpose please contact us at partner@przelewy24.pl');
            $this->messageManager->addSuccess($msg);
        }
    }

    private function validateGaKey()
    {
        $value = $this->getValue();
        if (!empty($value) && !preg_match('#^[A-Z]{2}\-\d+\-\d+$#', $value)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                new Phrase(
                    __('Przelewy24: Wrong Google Analytics key format. Acceptable format: UA-0123456-7')
                )
            );
        }
    }

    private function validateTimeLimit()
    {
        $value = (int)$this->getValue();
        if ($value < 0 || $value > 99) {
            throw new \Magento\Framework\Exception\LocalizedException(
                new Phrase(
                    __('Przelewy24: Incorrect time limit for completing the transaction. Valid values: from 0 to 99')
                )
            );
        }
    }

    private function validateExtraChargeAmount()
    {
        $value = strtr($this->getValue(), ',', '.');
        if (!empty($value) && !is_numeric($value)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                new Phrase(
                    __('Przelewy24: Incorrect amount for calculating additional payment')
                )
            );
        }
        $this->setValue($value);
    }

    private function validateExtraChargePercent()
    {
        $value = strtr($this->getValue(), ',', '.');
        if (!empty($value) && !is_numeric($value)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                new Phrase(
                    __('Przelewy24: Wrong amount percentage for additional fee')
                )
            );
        }
        $this->setValue($value);
    }

    /**
     * @return string
     */
    private function getMerchantId()
    {
        if ($this->scope == "SCOPE_WEBSITE")
        {
            return $this->scopeConfig->getValue(Data::XML_PATH_MERCHANT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $this->storeId);
        }
        else
        {
            return $this->scopeConfig->getValue(Data::XML_PATH_MERCHANT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
        }
    }

    /**
     * @return string
     */
    private function getShopId()
    {
        if ($this->scope == "SCOPE_WEBSITE")
        {
            return $this->scopeConfig->getValue(Data::XML_PATH_SHOP_ID, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $this->storeId);
        }
        else
        {
            return $this->scopeConfig->getValue(Data::XML_PATH_SHOP_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
        }
    }

    /**
     * @return string
     */
    private function getSalt()
    {
        if ($this->scope == "SCOPE_WEBSITE")
        {
            return $this->scopeConfig->getValue(Data::XML_PATH_SALT, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $this->storeId);
        }
        else
        {
            return $this->scopeConfig->getValue(Data::XML_PATH_SALT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
        }
    }

    /**
     * @return bool
     */
    private function getMode()
    {
        if ($this->scope == "SCOPE_WEBSITE")
        {
            return $this->scopeConfig->getValue(Data::XML_PATH_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $this->storeId) == '1';
        }
        else
        {
            return $this->scopeConfig->getValue(Data::XML_PATH_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId) == '1';
        }
    }

    private function getApiKey()
    {
        if ($this->scope == "SCOPE_WEBSITE")
        {
            return $this->scopeConfig->getValue(Data::XML_PATH_API_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $this->storeId);
        }
        else
        {
            return $this->scopeConfig->getValue(Data::XML_PATH_API_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
        }
    }
}

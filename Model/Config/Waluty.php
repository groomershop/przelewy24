<?php

namespace Dialcom\Przelewy\Model\Config;


use Dialcom\Przelewy\Helper\Data;

class Waluty
{
	/**
	 * @var \Magento\Framework\App\ObjectManager
	 */
	private $objectManager;
	/** @var \Magento\Store\Model\StoreManagerInterface */
	private $store;

	/**
	 * Product constructor.
	 */
	public function __construct()
	{
		$this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$this->store = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
	}

	public function toOptionArray() {
		$currencies = array();
		$codes = $this->store->getAvailableCurrencyCodes(true);
		if (is_array($codes) && count($codes) > 1) {
			foreach ($codes as $code) {
				if ($code != 'PLN') {
					$currencies[$code] = $code;
				}
			}
		}
		return $currencies;
	}

	public static function multicurrGetConfig($name, $currency = null, $default = null, $scopeConfig = null, $storeId = 0) {
		if (is_null($scopeConfig)) {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$scopeConfig = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
		}

		$configKey = Data::XML_PREFIX_MULTICURR . $name;
		$configValue = $scopeConfig->getValue($configKey, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

		$result = array();
		$vals = explode(',', $configValue);
		if (is_array($vals)) foreach ($vals as $item) {
			$items = explode(':', $item);
			if (is_array($items) && sizeof($items) == 2) {
				$result[$items[0]] = $items[1];
			}
		}
		if (!is_null($currency)) {
			if (isset($result[$currency]) && !empty($result[$currency])) {
				return $result[$currency];
			}
			return $default;
		}
		return $result;
	}

	public static function getFullConfig($currency = 'PLN', $scopeConfig = null, $storeId = 0, $scopeName = '') {
		if (is_null($scopeConfig)) {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$scopeConfig = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
		}
        $merchantId = (int)$scopeConfig->getValue(Data::XML_PATH_MERCHANT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $shopId = (int)$scopeConfig->getValue(Data::XML_PATH_SHOP_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $salt = $scopeConfig->getValue(Data::XML_PATH_SALT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $api = $scopeConfig->getValue(Data::XML_PATH_API_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

        if ($scopeName == 'SCOPE_WEBSITE')
        {
            $merchantId = (int)$scopeConfig->getValue(Data::XML_PATH_MERCHANT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $storeId);
            $shopId = (int)$scopeConfig->getValue(Data::XML_PATH_SHOP_ID, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $storeId);
            $salt = $scopeConfig->getValue(Data::XML_PATH_SALT, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $storeId);
            $api = $scopeConfig->getValue(Data::XML_PATH_API_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $storeId);
        }

		if ($currency != 'PLN') {
			$merchantId = self::multicurrGetConfig('merchantid', $currency, $merchantId, $scopeConfig, $storeId);
			$shopId = self::multicurrGetConfig('shopid', $currency, $shopId, $scopeConfig, $storeId);
			$salt = self::multicurrGetConfig('salt', $currency, $salt, $scopeConfig, $storeId);
			$api = self::multicurrGetConfig('api', $currency, $api, $scopeConfig, $storeId);
		}
		$ret = array(
			'merchant_id' => $merchantId,
			'shop_id' => $shopId,
			'salt' => $salt,
			'api' => $api
		);
		return $ret;
	}
}